<?php

namespace Database\Seeders;


use Dipokhalder\EnvEditor\EnvEditor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Smartisan\Settings\Facades\Settings;


class MailTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $envService = new EnvEditor();

        Settings::group('mail')->set([
            'mail_mailer'     => 'smtp',
            'mail_host'       => $envService->getValue('DEMO') ? 'mail.inilabs.dev' : '',
            'mail_port'       => $envService->getValue('DEMO') ? '465' : 0,
            'mail_username'   => $envService->getValue('DEMO') ? 'inilabsd@inilabs.dev' : '',
            'mail_password'   => $envService->getValue('DEMO') ? 'rb-XO$3~dc4q' : '',
            'mail_encryption' => $envService->getValue('DEMO') ? 'ssl' : '',
            'mail_from_name'  => $envService->getValue('DEMO') ? 'StoreKing - Grocery App with eCommerce Website & Admin Panel with POS | Inventory Management' : '',
            'mail_from_email' => $envService->getValue('DEMO') ? 'inilabsd@inilabs.dev' : ''
        ]);

        $envService->addData([
            'MAIL_MAILER'       => 'smtp',
            'MAIL_HOST'         => $envService->getValue('DEMO') ? 'mail.inilabs.dev' : '',
            'MAIL_PORT'         => $envService->getValue('DEMO') ? '465' : 0,
            'MAIL_USERNAME'     => $envService->getValue('DEMO') ? 'inilabsd@inilabs.dev' : '',
            'MAIL_PASSWORD'     => $envService->getValue('DEMO') ? 'rb-XO$3~dc4q' : '',
            'MAIL_ENCRYPTION'   => $envService->getValue('DEMO') ? 'ssl' : '',
            'MAIL_FROM_NAME'    => $envService->getValue('DEMO') ? 'StoreKing - Grocery App with eCommerce Website & Admin Panel with POS | Inventory Management' : '',
            'MAIL_FROM_ADDRESS' => $envService->getValue('DEMO') ? 'inilabsd@inilabs.dev' : ''
        ]);
        Artisan::call('optimize:clear');
    }
}
