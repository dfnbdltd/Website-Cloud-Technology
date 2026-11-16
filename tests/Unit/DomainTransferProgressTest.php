<?php

namespace Tests\Unit;

use App\Support\DomainActivity;
use App\Support\DomainTransferProgress;
use App\Support\DomainTransferStatus;
use App\Support\PendingTransferList;
use App\Support\ResellerClubOrderId;
use PHPUnit\Framework\TestCase;

/**
 * How ResellerClub's reports are read as "is this transfer done?".
 *
 * Pure logic, no framework and no network, so it extends the bare PHPUnit
 * TestCase. The action fields are the real search-actions field names.
 */
class DomainTransferProgressTest extends TestCase
{
    private function action($type, $title, $status, $description = '', $completed = '1794308804')
    {
        return DomainActivity::normalize([
            'eaqid' => '1',
            'entityid' => '127547539',
            'actiontype' => $type,
            'actiontypedesc' => $title,
            'actionstatus' => $status,
            'actionstatusdesc' => $description,
            'actionadded' => '1793859165',
            'actionstarted' => '1793859165',
            'actioncompleted' => $completed,
        ]);
    }

    public function testCompletedTransferActionIsDoneAndCarriesTheExpiry()
    {
        $done = $this->action('AddTransferDomain', 'Transfer of netsgoods.com from old Registrar along with 1 year Renewal', 'Success');

        $result = DomainTransferProgress::evaluate([$done], ['currentstatus' => 'Active', 'endtime' => '1834272000']);

        $this->assertSame(DomainTransferProgress::COMPLETED, $result['state']);
        $this->assertSame(1834272000, $result['expires_at']);
        $this->assertSame(1794308804, $result['completed_at']);
    }

    public function testTheTransferIsRecognisedByItsDescriptionAlone()
    {
        $done = $this->action('', 'Transfer of netsgoods.com from old Registrar along with 1 year Renewal', 'Success');

        $this->assertSame(DomainTransferProgress::COMPLETED, DomainTransferProgress::evaluate([$done], null)['state']);
    }

    public function testAnActiveOrderIsDoneEvenWhenNoActionCouldBeRead()
    {
        $this->assertSame(DomainTransferProgress::COMPLETED, DomainTransferProgress::evaluate([], ['currentstatus' => 'Active'])['state']);
    }

    public function testAPendingTransferStaysPending()
    {
        $pending = $this->action('AddTransferDomain', 'Transfer of x.com from old Registrar', 'Pending', '', '');

        $this->assertSame(DomainTransferProgress::PENDING, DomainTransferProgress::evaluate([$pending], ['currentstatus' => 'InActive'])['state']);
        $this->assertSame(DomainTransferProgress::PENDING, DomainTransferProgress::evaluate([], ['currentstatus' => 'InActive'])['state']);
        $this->assertSame(DomainTransferProgress::PENDING, DomainTransferProgress::evaluate(null, null)['state']);
    }

    public function testARejectedTransferFailsAndFailureBeatsAnActiveOrder()
    {
        $failed = $this->action('AddTransferDomain', 'Transfer of x.com from old Registrar', 'Failed', 'Rejected by the losing registrar');

        $this->assertSame(DomainTransferProgress::FAILED, DomainTransferProgress::evaluate([$failed], ['currentstatus' => 'InActive'])['state']);
        $this->assertSame(DomainTransferProgress::FAILED, DomainTransferProgress::evaluate([$failed], ['currentstatus' => 'Active'])['state']);
    }

    public function testOtherActionsThatMentionTransferAreNotTheTransfer()
    {
        $lock = $this->action('ModTheftProtection', 'Modification of Transfer Lock of x.com', 'Success');
        $ns = $this->action('ModNs', 'Modification of Name Servers of x.com', 'Success');

        $this->assertSame(DomainTransferProgress::PENDING, DomainTransferProgress::evaluate([$lock, $ns], ['currentstatus' => 'InActive'])['state']);
    }

    public function testStatusLabelsFollowTheItemNotJustTheOrder()
    {
        $this->assertSame('success', DomainTransferStatus::forOrderItem('transfer_pending', 'paid', 'transferred')['key']);
        $this->assertSame('success', DomainTransferStatus::forOrderItem('active', 'paid', 'transfer_submitted')['key']);
        $this->assertSame('pending', DomainTransferStatus::forOrderItem('transfer_pending', 'paid', 'transfer_submitted')['key']);
        $this->assertSame('failed', DomainTransferStatus::forOrderItem('transfer_pending', 'paid', 'transfer_failed')['key']);
    }

    public function testTheResellerClubOrderIdIsReadFromTheTransferResponse()
    {
        // transfer.json documents the order as `entityid`; `customerid` is not the order.
        $this->assertSame('127547539', ResellerClubOrderId::fromResponse(['status' => 'NoError', 'entityid' => '127547539', 'customerid' => '14569999']));
        $this->assertSame('127547539', ResellerClubOrderId::fromResponse(['data' => ['orderId' => 127547539]]));
        $this->assertNull(ResellerClubOrderId::fromResponse(['status' => 'Success', 'customerid' => '14569999']));
        $this->assertNull(ResellerClubOrderId::fromResponse(['entityid' => 'abc']));
        $this->assertNull(ResellerClubOrderId::fromResponse(null));
    }

    public function testTheV2PendingListIsParsedByDomain()
    {
        $list = PendingTransferList::parse([
            'transfers' => [
                ['actionId' => '1', 'actionStatus' => 'Pending', 'domainName' => 'NetsGoods.com', 'orderId' => '127547539', 'customerId' => '14569999'],
                ['domainName' => 'other.org', 'orderId' => 'x'],
                ['orderId' => '9'],
            ],
            'totalItems' => 2, 'itemsOnPage' => 2, 'status' => 'Success',
        ]);

        $this->assertSame(['netsgoods.com', 'other.org'], array_keys($list['transfers']));
        $this->assertSame('127547539', $list['transfers']['netsgoods.com']['order_id']);
        $this->assertNull($list['transfers']['other.org']['order_id']);
        $this->assertSame(2, $list['total']);
    }

    public function testAnErrorOrUnexpectedAnswerIsNeverAnEmptyPendingList()
    {
        $this->assertNull(PendingTransferList::parse(null));
        $this->assertNull(PendingTransferList::parse(['status' => 'Failed', 'message' => 'denied']));
        $this->assertNull(PendingTransferList::parse(['error' => 'denied']));
        $this->assertNull(PendingTransferList::parse(['unrelated' => true]));
        $this->assertSame([], PendingTransferList::parse(['transfers' => [], 'totalItems' => 0, 'status' => 'Success'])['transfers']);
        $this->assertSame([], PendingTransferList::parse(['totalItems' => 0])['transfers']);
    }

    public function testFailedLookingActionStatusesAreRecognised()
    {
        $this->assertTrue(PendingTransferList::looksFailed('Failed'));
        $this->assertTrue(PendingTransferList::looksFailed('Rejected by losing registrar'));
        $this->assertFalse(PendingTransferList::looksFailed('Pending'));
        $this->assertFalse(PendingTransferList::looksFailed(''));
    }

    public function testTheV2TransferResponseOrderIdIsRead()
    {
        // v2 transfer-in answers `entityId`; privacyDetails carries a second, separate block
        $this->assertSame('127547539', ResellerClubOrderId::fromResponse(['status' => 'Success', 'entityId' => '127547539', 'privacyDetails' => ['entityId' => '1']]));
    }
}
