<?php

namespace WHMCS\Module\Mail;

/**
 * Brevo Mail Provider for WHMCS
 *
 * @author     Rohmat Ali Wardani
 * @copyright  2024
 * @license    MIT License
 * @version    1.0.0
 * @link       https://www.linkedin.com/in/rohmat-ali-wardani/
 */

use WHMCS\Authentication\CurrentUser;
use WHMCS\Exception\Mail\SendFailure;
use WHMCS\Mail\Message;
use WHMCS\Module\Contracts\SenderModuleInterface;
use WHMCS\Module\MailSender\DescriptionTrait;
use WHMCS\Config\Setting;

class Brevo implements SenderModuleInterface
{
    use DescriptionTrait;

    private $apiUrl = 'https://api.sendinblue.com/v3/smtp/email';
    private $author = 'Rohmat Ali Wardani';
    private $authorUrl = 'https://www.linkedin.com/in/rohmat-ali-wardani/';
    private $version = '1.0.0';

    private function getWhmcsEmailSettings()
    {
        $email = Setting::getValue('Email');
        $companyName = Setting::getValue('CompanyName');

        // Log untuk debugging
        error_log('WHMCS Email: ' . $email);
        error_log('WHMCS Company: ' . $companyName);

        if (empty($email)) {
            throw new \Exception('Email pengirim belum diatur di WHMCS. Silakan atur di Setup > General Settings > General.');
        }

        return [
            'from_email' => $email,
            'from_name' => $companyName ?: 'WHMCS System'
        ];
    }

    public function settings()
    {
        return [
            'apiKey' => [
                'FriendlyName' => 'API Key',
                'Type' => 'password',
                'Description' => 'Masukkan API key V3 dari Brevo',
                'Required' => true,
            ],
            'use_whmcs_email' => [
                'FriendlyName' => 'Gunakan Email WHMCS',
                'Type' => 'yesno',
                'Description' => 'Gunakan email dari pengaturan WHMCS (Setup > General Settings > General)',
                'Default' => 'yes',
                'Required' => true,
            ],
            'from_email' => [
                'FriendlyName' => 'From Email',
                'Type' => 'text',
                'Description' => 'Alamat email pengirim (wajib diisi jika tidak menggunakan email WHMCS)',
                'Required' => false,
            ],
            'from_name' => [
                'FriendlyName' => 'From Name',
                'Type' => 'text',
                'Description' => 'Nama pengirim (opsional, akan menggunakan nama perusahaan dari WHMCS jika kosong)',
                'Required' => false,
            ],
            'author_info' => [
                'FriendlyName' => 'Developer Info',
                'Type' => 'readonly',
                'Description' => 'Developed by <a href="' . $this->authorUrl . '" target="_blank">' . $this->author . '</a>',
            ],
        ];
    }

    private function getSenderDetails(array $settings)
    {
        try {
            // Log untuk debugging
            error_log('Settings: ' . print_r($settings, true));
            error_log('Use WHMCS Email: ' . $settings['use_whmcs_email']);

            // Perbaikan pengecekan use_whmcs_email
            if (isset($settings['use_whmcs_email']) && ($settings['use_whmcs_email'] === 'yes' || $settings['use_whmcs_email'] === 'on' || $settings['use_whmcs_email'] == 1)) {
                $whmcsSettings = $this->getWhmcsEmailSettings();
                
                error_log('WHMCS Settings: ' . print_r($whmcsSettings, true));
                
                // Pastikan email tidak kosong
                if (empty($whmcsSettings['from_email'])) {
                    throw new \Exception('Email pengirim dari WHMCS tidak valid');
                }

                return [
                    'email' => $whmcsSettings['from_email'],
                    'name' => $whmcsSettings['from_name']
                ];
            }

            // Jika menggunakan custom email
            if (empty($settings['from_email'])) {
                throw new \Exception('Email pengirim harus diisi jika tidak menggunakan email WHMCS');
            }

            return [
                'email' => $settings['from_email'],
                'name' => $settings['from_name'] ?: 'WHMCS System'
            ];
        } catch (\Exception $e) {
            error_log('Brevo getSenderDetails Error: ' . $e->getMessage());
            throw new \Exception('Konfigurasi email pengirim tidak valid: ' . $e->getMessage());
        }
    }

    public function getName()
    {
        return 'Brevo';
    }

    public function getDisplayName()
    {
        return 'Brevo Mail Provider by ' . $this->author;
    }

    public function getAuthorInfo()
    {
        return [
            'name' => $this->author,
            'url' => $this->authorUrl,
            'version' => $this->version
        ];
    }

    private function sendRequest($endpoint, $data, $apiKey)
    {
        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'Content-Type: application/json',
            'api-key: ' . $apiKey
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new \Exception("cURL Error: $error");
        }

        if ($httpCode >= 400) {
            $responseData = json_decode($response, true);
            throw new \Exception(isset($responseData['message']) ? $responseData['message'] : "HTTP Error: $httpCode");
        }

        return json_decode($response, true);
    }

    public function testConnection(array $settings)
    {
        $currentAdmin = (new CurrentUser)->admin();

        try {
            $sender = $this->getSenderDetails($settings);
            
            // Log untuk debugging
            error_log('Test Connection Sender: ' . print_r($sender, true));

            $data = [
                'sender' => [
                    'name' => $sender['name'],
                    'email' => $sender['email']
                ],
                'to' => [
                    ['email' => $currentAdmin->email]
                ],
                'subject' => 'Brevo Test Connection',
                'htmlContent' => 'Email ini dikirim untuk mengetes konfigurasi mail provider Brevo.<br><br>' .
                                '<small>Powered by Brevo Mail Provider<br>' .
                                'Developed by <a href="' . $this->authorUrl . '">' . $this->author . '</a></small>'
            ];

            $this->sendRequest($this->apiUrl, $data, $settings['apiKey']);

            return [
                'success' => true,
                'message' => 'Koneksi berhasil dan email test terkirim!'
            ];
        } catch (\Exception $e) {
            error_log('Brevo API Error: ' . $e->getMessage());
            throw new \Exception("Gagal melakukan test koneksi: " . $e->getMessage());
        }
    }

    public function send(array $settings, Message $message)
    {
        try {
            $sender = $this->getSenderDetails($settings);
            
            $data = [
                'sender' => [
                    'name' => $sender['name'],
                    'email' => $sender['email']
                ],
                'to' => [],
                'subject' => $message->getSubject()
            ];

            // Set recipients
            foreach ($message->getRecipients('to') as $to) {
                $data['to'][] = ['email' => $to[0], 'name' => $to[1]];
            }

            // Set CC
            if ($cc = $message->getRecipients('cc')) {
                $data['cc'] = array_map(function($recipient) {
                    return ['email' => $recipient[0], 'name' => $recipient[1]];
                }, $cc);
            }

            // Set BCC
            if ($bcc = $message->getRecipients('bcc')) {
                $data['bcc'] = array_map(function($recipient) {
                    return ['email' => $recipient[0], 'name' => $recipient[1]];
                }, $bcc);
            }

            // Set content
            $body = $message->getBody();
            if ($body) {
                $branding = '<br><br><small style="color:#666;">Powered by Brevo Mail Provider<br>' .
                           'Developed by <a href="' . $this->authorUrl . '">' . $this->author . '</a></small>';
                $data['htmlContent'] = $body . $branding;
                $data['textContent'] = $message->getPlainText() ?: strip_tags($body);
            } else {
                $data['textContent'] = $message->getPlainText();
            }

            // Set Reply-To
            if ($replyTo = $message->getReplyTo()) {
                $data['replyTo'] = [
                    'email' => $replyTo['email'],
                    'name' => $replyTo['name']
                ];
            }

            // Set attachments
            if ($attachments = $message->getAttachments()) {
                $data['attachment'] = [];
                foreach ($attachments as $attachment) {
                    if (isset($attachment['data'])) {
                        $content = base64_encode($attachment['data']);
                    } else {
                        $content = base64_encode(file_get_contents($attachment['filepath']));
                    }
                    $data['attachment'][] = [
                        'name' => $attachment['filename'],
                        'content' => $content
                    ];
                }
            }

            $this->sendRequest($this->apiUrl, $data, $settings['apiKey']);

        } catch (\Exception $e) {
            error_log('Brevo Send Error: ' . $e->getMessage());
            throw new SendFailure("Gagal mengirim email: " . $e->getMessage());
        }
    }
} 