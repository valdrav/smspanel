<?php

namespace Database\Seeders;

use App\Models\SmsPackage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Örnek SMS paketlerini oluşturur.
 */
class SmsPackageSeeder extends Seeder
{
    public function run(): void
    {
        $packages = [
            [
                'name' => 'Başlangıç',
                'description' => 'Küçük işletmeler ve deneme gönderimleri için ideal giriş paketi.',
                'badge' => 'Giriş',
                'features' => [
                    '1.000 SMS hakkı',
                    'Tekil ve toplu gönderim',
                    'Rehber desteği',
                    'SMS geçmişi ve raporlar',
                ],
                'theme' => 'cyan',
                'sms_amount' => 1000,
                'price' => 149.90,
                'is_active' => true,
                'is_public' => true,
                'is_featured' => false,
                'sort_order' => 10,
            ],
            [
                'name' => 'Profesyonel',
                'description' => 'Düzenli kampanya yapan işletmeler için en çok tercih edilen paket.',
                'badge' => 'En Popüler',
                'features' => [
                    '5.000 SMS hakkı',
                    'Kampanya gönderimi',
                    'Şablon kullanımı',
                    'Öncelikli destek',
                    'Detaylı raporlama',
                ],
                'theme' => 'indigo',
                'sms_amount' => 5000,
                'price' => 599.00,
                'is_active' => true,
                'is_public' => true,
                'is_featured' => true,
                'sort_order' => 20,
            ],
            [
                'name' => 'Kurumsal',
                'description' => 'Yüksek hacimli gönderimler ve kurumsal ekipler için.',
                'badge' => 'Kurumsal',
                'features' => [
                    '20.000 SMS hakkı',
                    'Büyük kampanya kapasitesi',
                    'API erişimi',
                    'Özel gönderici başlığı desteği',
                    '7/24 destek',
                ],
                'theme' => 'emerald',
                'sms_amount' => 20000,
                'price' => 1999.00,
                'is_active' => true,
                'is_public' => true,
                'is_featured' => false,
                'sort_order' => 30,
            ],
            [
                'name' => 'Mega',
                'description' => 'Ajanslar ve yoğun dönem kampanyaları için yüksek kapasite.',
                'badge' => 'Yüksek Hacim',
                'features' => [
                    '50.000 SMS hakkı',
                    'Parçalı kampanya kuyruğu',
                    'Toplu rehber içe aktarma',
                    'Özel fiyat avantajı',
                ],
                'theme' => 'amber',
                'sms_amount' => 50000,
                'price' => 4499.00,
                'is_active' => true,
                'is_public' => true,
                'is_featured' => false,
                'sort_order' => 40,
            ],
            [
                'name' => 'Özel Teklif',
                'description' => 'İsteğe özel hacim ve fiyatlandırma. Talebinizi not ile iletin.',
                'badge' => 'Teklif Alın',
                'features' => [
                    'Esnek SMS miktarı',
                    'Kuruma özel anlaşma',
                    'Özel destek hattı',
                ],
                'theme' => 'rose',
                'sms_amount' => 100000,
                'price' => null,
                'is_active' => true,
                'is_public' => true,
                'is_featured' => false,
                'sort_order' => 50,
            ],
        ];

        foreach ($packages as $data) {
            $slug = Str::slug($data['name']);

            SmsPackage::updateOrCreate(
                ['slug' => $slug],
                $data + ['slug' => $slug]
            );
        }
    }
}
