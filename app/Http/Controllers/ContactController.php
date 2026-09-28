<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function sendMessage(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'message' => ['required', 'string', 'max:5000'],
            'g-recaptcha-response' => ['required', 'string'],
        ]);

        if (!$this->captchaIsValid($data['g-recaptcha-response'], $request->ip())) {
            return back()->withErrors(['captcha' => 'Please complete the reCAPTCHA challenge.'])->withInput();
        }

        $messageData = $data;
        unset($messageData['g-recaptcha-response']);
        $message = ContactMessage::create($messageData);
        $recipient = config('services.contact.to');
        if ($recipient) {
            try {
                Mail::raw($data['message'], function ($mail) use ($data, $recipient) {
                    $mail->to($recipient)->replyTo($data['email'], $data['name'])->subject('New website contact message');
                });
            } catch (\Throwable $exception) {
                Log::error('Contact notification email failed', ['message_id' => $message->id, 'error' => $exception->getMessage()]);
            }
        }

        return back()->with('success', 'Your message has been received.');
    }

    private function captchaIsValid($token, $ip)
    {
        $secret = config('services.recaptcha.secret');
        if (!$secret) {
            return app()->environment('local', 'testing');
        }

        $curl = curl_init('https://www.google.com/recaptcha/api/siteverify');
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query(['secret' => $secret, 'response' => $token, 'remoteip' => $ip]),
            CURLOPT_TIMEOUT => 8,
        ]);
        $response = curl_exec($curl);
        curl_close($curl);
        $decoded = json_decode($response ?: '', true);

        return !empty($decoded['success']);
    }
}