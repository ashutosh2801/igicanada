<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('igi_email_template', function (Blueprint $table): void {
            $table->string('sales_channel', 20)->default('wholesale')->index()->after('id');
        });

        $wholesale = DB::table('igi_email_template')->where('name', 'active-mail-to-user')->where('sales_channel', 'wholesale')->orderBy('id')->first();

        DB::table('igi_email_template')->insertOrIgnore([
            'name' => 'active-mail-to-user',
            'subject' => 'www.LeatherWallets.ca Account Activation Update.',
            'content' => '<p>Dear {{name}},</p>
<p>We thank you for registering at www.LeatherWallets.ca.</p>
<p>Your account has now been activated. You can now place retail orders online at retail prices.</p>
<p>If you have any problems in accessing the website, please write to us at info@leatherwallets.ca. You may also call us at 905-625-8831 during regular business hours for any queries.</p>
<p>We look forward to serving you.</p>
<p><span style="font-weight: bold;">Your details are:</span></p>
<p>Username: {{username}}</p>
<p>Password: {{password}}</p>
<p>Click here to <a href="{{login_link}}" target=""> login</a></p>
<p>Thank you.</p>
<p>Team www.LeatherWallets.ca</p>
<p>---------------------------------------------------------------------------------</p>
<h6>The information contained in this e-mail and any attachments is privileged, proprietary and confidential and is intended only for the use of the person(s) to whom it is addressed. This email is for informational purposes only and is subject to errors and/or omissions. If the reader of this e-mail is not the intended recipient, you are hereby notified that any review, copying, distribution, disclosure or other use of this e-mail and its contents is strictly prohibited. By opening this e-mail or its attachments you agree to uphold the proprietary and confidential nature of this message.</h6>
<h6>If you have received this e-mail in error, please delete it and any attachments and notify us immediately by replying to the message.</h6>',
            'status' => $wholesale?->status ?? 1,
            'sales_channel' => 'retail',
        ]);

        DB::table('igi_email_template')->insertOrIgnore([
            'name' => 'non-acceptance-mail-to-user',
            'subject' => 'www.LeatherWallets.ca Account Registration Update',
            'content' => '<p>Dear Customer,</p>
<p>We thank you for registering at <a href="https://www.LeatherWallets.ca" target="_blank">www.LeatherWallets.ca</a>.</p>
<p>Your application for an account was not accepted at this time.</p>
<p>You may send your specific inquiries to info@leatherwallets.ca or call us at 905-625-8831 for further assistance.</p>
<p>Thank you.</p>
<p>Team www.LeatherWallets.ca</p>
<p>--------------------------------------------------------------------------------</p>
<h6>The information contained in this e-mail and any attachments is privileged, proprietary and confidential and is intended only for the use of the person(s) to whom it is addressed. This email is for informational purposes only and is subject to errors and/or omissions. If the reader of this e-mail is not the intended recipient, you are hereby notified that any review, copying, distribution, disclosure or other use of this e-mail and its contents is strictly prohibited. By opening this e-mail or its attachments you agree to uphold the proprietary and confidential nature of this message.</h6>
<h6>If you have received this e-mail in error, please delete it and any attachments and notify us immediately by replying to the message.</h6>',
            'status' => 1,
            'sales_channel' => 'retail',
        ]);
    }

    public function down(): void
    {
        DB::table('igi_email_template')->where('sales_channel', 'retail')->delete();

        Schema::table('igi_email_template', function (Blueprint $table): void {
            $table->dropColumn('sales_channel');
        });
    }
};
