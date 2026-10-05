<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReveChatWidgetTest extends TestCase
{
    public function testPublicPagesRenderReveChatButtonButDoNotLoadTheVendorWidgetUntilClicked()
    {
        $response = $this->get('/coming-soon/support');

        $response->assertStatus(200);
        $response->assertSee('id="reveChatFab"', false);
        $response->assertSee('id="reveChatMinimize"', false);
        $response->assertSee('data-revechat-account="7494338"', false);
        $response->assertSee('js/revechat.js', false);
        $response->assertSee('css/revechat.css', false);
        $response->assertDontSee('static.revechat.com/widget/scripts/new-livechat.js');
    }

    public function testCustomerAuthenticationPagesAlsoIncludeReveChatEntryPoint()
    {
        foreach (['/login', '/register', '/password/reset'] as $uri) {
            $this->get($uri)
                ->assertStatus(200)
                ->assertSee('id="reveChatFab"', false);
        }
    }

    public function testCustomLauncherAndReveCollapsedButtonCannotOverlap()
    {
        $script = file_get_contents(public_path('js/revechat.js'));
        $styles = file_get_contents(public_path('css/revechat.css'));

        $this->assertStringContainsString('#reve-chat-widget-holder-2{visibility:hidden!important}', $script);
        $this->assertStringContainsString('.revechat-fab[hidden]{display:none!important}', $styles);
        $this->assertStringContainsString('holder.style.visibility = \'hidden\'', $script);
        $this->assertStringContainsString('holder.style.pointerEvents = \'none\'', $script);
        $this->assertStringContainsString('.revechat-minimize[hidden]{display:none!important}', $styles);
        $this->assertStringContainsString('holder.getBoundingClientRect()', $script);
        $this->assertStringContainsString('bounds.width >= 200', $script);
        $this->assertStringContainsString('bounds.height >= 200', $script);
        $this->assertStringContainsString('stateWatcher = window.setInterval(syncWidgetState, 500)', $script);
    }
}
