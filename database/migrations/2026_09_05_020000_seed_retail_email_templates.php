<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var array<string, array{subject: string, content: string}> */
    private const RETAIL_TEMPLATES = [
        'retailer-signup-mail-to-admin' => [
            'subject' => 'New registration form www.LeatherWallets.ca',
            'content' => '<p>Dear Admin</p>
<p>One new user has created an account.</p>
<p>Details are: </p><p>Name:{{name}}</p>
<p>Email:{{email}}</p><p>Type: {{type}}</p>
<p>Thanks</p><p>{{name}}<br></p>',
        ],
        'contact-us-mail-to-admin' => [
            'subject' => 'New Contact Us',
            'content' => '<p>Dear Admin</p><p>New Contact us details are:
</p><p>Name: {{name}}
</p><p>Email: {{email}}
</p><p>Phone: {{phone}}
</p><p>Message{{message}}
</p><p>
Thanks,
</p><p>www.LeatherWallets.ca Team</p>',
        ],
        'forgot-mail-to-user' => [
            'subject' => 'New password from www.LeatherWallets.ca',
            'content' => '<p>Dear {{name}},</p>
<p>Click here to change your password
<a href="{{edit_link}}">Update</a></p><p>

</p><p>Thanks,</p>
<p>www.LeatherWallets.ca Team</p>',
        ],
        'order-place-mail-to-admin' => [
            'subject' => 'New order from www.LeatherWallets.ca',
            'content' => '<p>Dear admin,
</p><p>
New Order has been placed.
</p><p>
Details are below:</p><p>

{{message}}
</p><p>
Thanks</p>',
        ],
        'order-place-mail-to-user' => [
            'subject' => 'Product order detail from www.LeatherWallets.ca',
            'content' => '<p>Dear {{name}},
</p><p>
New Order has been placed.
</p><p>
Details are below:
</p><p>
{{message}}
</p><p>
Thanks
</p><p>
www.LeatherWallets.ca Team</p>',
        ],
        'contact-us-mail-to-user' => [
            'subject' => 'Contact us mail',
            'content' => '<p>Dear {{name}}
</p><p>
Thank you for connecting us.
</p><p>
Thanks,
</p><p>
www.LeatherWallets.ca Team</p>',
        ],
        'retailer-signup-mail-to-user' => [
            'subject' => 'Thank you for registering with us.',
            'content' => '<p>Hi {{name}},</p>
<p>Thank you for registering with us.</p>
<p>{{coupon}}</p>
<p>Click here to <a href="{{login_link}}" target="">Login</a></p>
<p>Thank You,</p>
<p>www.LeatherWallets.ca Team</p>
<p><br></p>',
        ],
        'product-inquiry-mail-to-admin' => [
            'subject' => 'Product Inquiry from www.LeatherWallets.ca',
            'content' => '<p>Hi Admin,</p><p>{{name}}</p><p>{{email}}</p><p>{{message}}</p><p>Thanks</p><p>{{name}}</p>',
        ],
        'status-change-mail-to-user' => [
            'subject' => 'Your #{{order}} order had been changed {{status}} status.',
            'content' => '<p>Hi {{name}},</p>
<p>Your #{{order}} order had been changed {{status}} status.</p><p>{{courier_provider}}</p><p>{{track_no}}<br></p>
<p>Thanks,</p>
<p>www.LeatherWallets.ca Team</p>',
        ],
        'newsletter-mail-to-user' => [
            'subject' => 'Thank you for subscribing us',
            'content' => '<p>Hi,</p><p>Thank you for subscribing us.</p><p>Thank You</p><p>www.LeatherWallets.ca</p>',
        ],
        'newsletter-mail-to-admin' => [
            'subject' => 'New subscriber from www.LeatherWallets.ca',
            'content' => '<p>Hi admin,</p><p>New subscriber detail is:</p><p>Email: {{email}}</p><p>Thank You</p><p>{{email}}</p>',
        ],
        'register-mail-by-admin' => [
            'subject' => 'Congratulations! Your account has been created.',
            'content' => '<p>Hi {{name}},</p><p>Congratulations! Your account has been created.</p><p>Your details are:</p><p>Username: {{username}}</p><p>Password: {{password}}</p><p>Click here to<a href="{{login_link}}" target=""> login</a></p><p>Thank You,</p><p>www.LeatherWallets.ca Team</p><p><br></p>',
        ],
    ];

    public function up(): void
    {
        foreach (self::RETAIL_TEMPLATES as $name => $template) {
            $exists = DB::table('igi_email_template')
                ->where('name', $name)
                ->where('sales_channel', 'retail')
                ->whereNotNull('subject')
                ->exists();

            if ($exists || ! Schema::hasTable('igi_email_template')) {
                continue;
            }

            DB::table('igi_email_template')->insertOrIgnore([
                'name' => $name,
                'subject' => $template['subject'],
                'content' => $template['content'],
                'status' => 1,
                'sales_channel' => 'retail',
            ]);
        }
    }

    public function down(): void
    {
        DB::table('igi_email_template')
            ->where('sales_channel', 'retail')
            ->whereIn('name', array_keys(self::RETAIL_TEMPLATES))
            ->delete();
    }
};
