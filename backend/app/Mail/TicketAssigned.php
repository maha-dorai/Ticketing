<?php

namespace App\Mail;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TicketAssigned extends Mailable
{
    use Queueable, SerializesModels;

    public Ticket $ticket;
    public User   $recipient;
    public string $role; // 'developpeur' ou 'testeur'

    public function __construct(Ticket $ticket, User $recipient, string $role)
    {
        $this->ticket    = $ticket;
        $this->recipient = $recipient;
        $this->role      = $role;
    }

    public function build(): self
    {
        $subject = $this->role === 'developpeur'
            ? "🎫 Nouveau ticket assigné : {$this->ticket->titre}"
            : "🎫 Ticket créé : {$this->ticket->titre}";

        return $this->subject($subject)->html($this->buildHtml());
    }

    private function buildHtml(): string
    {
        $prenom   = htmlspecialchars($this->recipient->prenom);
        $titre    = htmlspecialchars($this->ticket->titre);
        $desc     = htmlspecialchars($this->ticket->description ?? 'Aucune description.');
        $priorite = $this->ticket->priorite ?? 'BASSE';
        $projet   = htmlspecialchars($this->ticket->project->nom ?? '—');
        $url      = rtrim(env('FRONTEND_URL', 'http://localhost:5173'), '/') . '/tickets/' . $this->ticket->id;

        $roleLabel = $this->role === 'developpeur' ? 'développeur assigné' : 'testeur créateur';

        $priorityColor = match($priorite) {
            'CRITIQUE' => '#dc2626',
            'HAUTE'    => '#ea580c',
            'MOYENNE'  => '#d97706',
            default    => '#16a34a',
        };

        return <<<HTML
        <!DOCTYPE html>
        <html lang="fr">
        <head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
        <body style="margin:0;padding:0;background:#f4f6f9;font-family:'Segoe UI',Arial,sans-serif;">
          <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f9;padding:40px 0;">
            <tr><td align="center">
              <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.08);">

                <!-- Header -->
                <tr>
                  <td style="background:linear-gradient(135deg,#1e40af,#3b82f6);padding:32px 40px;text-align:center;">
                    <h1 style="margin:0;color:#fff;font-size:22px;font-weight:700;">🎫 Ticket assigné</h1>
                    <p style="margin:6px 0 0;color:#bfdbfe;font-size:14px;">Système de gestion de tickets</p>
                  </td>
                </tr>

                <!-- Body -->
                <tr>
                  <td style="padding:36px 40px;">
                    <p style="margin:0 0 20px;color:#1e293b;font-size:16px;">Bonjour <strong>{$prenom}</strong>,</p>
                    <p style="margin:0 0 24px;color:#475569;font-size:15px;line-height:1.6;">
                      Vous avez été désigné(e) comme <strong>{$roleLabel}</strong> sur le ticket suivant :
                    </p>

                    <!-- Ticket Card -->
                    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;margin-bottom:28px;">
                      <tr>
                        <td style="padding:24px 28px;">
                          <h2 style="margin:0 0 16px;color:#1e293b;font-size:18px;">{$titre}</h2>
                          <table width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                              <td style="padding:6px 0;color:#64748b;font-size:13px;width:120px;">📁 Projet</td>
                              <td style="padding:6px 0;color:#1e293b;font-size:13px;font-weight:600;">{$projet}</td>
                            </tr>
                            <tr>
                              <td style="padding:6px 0;color:#64748b;font-size:13px;">⚡ Priorité</td>
                              <td style="padding:6px 0;">
                                <span style="background:{$priorityColor};color:#fff;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:700;">{$priorite}</span>
                              </td>
                            </tr>
                            <tr>
                              <td style="padding:6px 0;color:#64748b;font-size:13px;vertical-align:top;">📝 Description</td>
                              <td style="padding:6px 0;color:#334155;font-size:13px;line-height:1.5;">{$desc}</td>
                            </tr>
                          </table>
                        </td>
                      </tr>
                    </table>

                    <!-- CTA Button -->
                    <table width="100%" cellpadding="0" cellspacing="0">
                      <tr>
                        <td align="center">
                          <a href="{$url}" style="display:inline-block;background:linear-gradient(135deg,#1e40af,#3b82f6);color:#fff;text-decoration:none;padding:14px 36px;border-radius:8px;font-size:15px;font-weight:600;letter-spacing:.3px;">
                            👁 Voir le ticket
                          </a>
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>

                <!-- Footer -->
                <tr>
                  <td style="background:#f8fafc;border-top:1px solid #e2e8f0;padding:20px 40px;text-align:center;">
                    <p style="margin:0;color:#94a3b8;font-size:12px;">Cet email a été envoyé automatiquement — merci de ne pas y répondre.</p>
                  </td>
                </tr>

              </table>
            </td></tr>
          </table>
        </body>
        </html>
        HTML;
    }
}