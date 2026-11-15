<?php

namespace App\Domain\Auth\Mail;

use App\Domain\Auth\Models\UserInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public UserInvitation $invitation
    ) {}

    public function envelope(): Envelope
    {
        $institutionName = $this->invitation->institution?->name ?? 'Beyan Madrasa';

        return new Envelope(
            subject: "Invitation to join {$institutionName}",
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: "
                <h2>Invitation to join {$this->invitation->institution?->name}</h2>
                <p>You have been invited to join the madrasa management system with the following role(s):</p>
                <ul>
                    ".implode('', array_map(fn ($r) => "<li><strong>{$r}</strong></li>", $this->invitation->roles))."
                </ul>
                <p>Click the link below to accept your invitation and complete your account setup:</p>
                <p><a href='{$this->invitation->accept_url}' style='background: #059669; color: #fff; padding: 10px 20px; border-radius: 6px; text-decoration: none; display: inline-block;'>Accept Invitation</a></p>
                <p>Or copy this link: {$this->invitation->accept_url}</p>
                <p><small>This invitation expires on {$this->invitation->expires_at->toFormattedDateString()}.</small></p>
            "
        );
    }
}
