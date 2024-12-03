# Brevo Mail Provider for WHMCS

Modul mail provider WHMCS untuk mengirim email menggunakan Brevo (sebelumnya Sendinblue).

## Fitur
- Kirim email menggunakan API Brevo V3
- Support HTML dan Plain Text email
- Support CC dan BCC
- Support Reply-To
- Support Attachments
- Test koneksi dan pengiriman email

## Instalasi
1. Download atau clone repository ini
2. Upload folder `brevo` ke folder `modules/mail/` di instalasi WHMCS Anda
3. Set permission folder dan file: 

```
chmod 755 modules/mail/brevo
chmod 644 modules/mail/brevo/*.php
```

4. Bersihkan cache WHMCS:
   - Pergi ke Utilities > System > System Cleanup
   - Centang semua opsi
   - Klik "Start Cleanup"

## Konfigurasi
1. Pergi ke Setup > General Settings > Mail
2. Pilih "Brevo" dari dropdown Mail Provider
3. Masukkan API Key V3 dari Brevo
4. Masukkan email dan nama pengirim
5. Klik "Test Connection" untuk memastikan konfigurasi sudah benar
6. Simpan pengaturan

## Persyaratan
- WHMCS 7.0 atau lebih baru
- PHP 7.2 atau lebih baru
- cURL extension
- Akun Brevo (https://www.brevo.com)
- API Key V3 dari Brevo

## Author
Rohmat Ali Wardani
- LinkedIn: [@rohmat-ali-wardani](https://www.linkedin.com/in/rohmat-ali-wardani/)

## License
MIT License

## Support
Jika Anda menemukan bug atau memiliki saran, silakan buat issue di repository ini.
