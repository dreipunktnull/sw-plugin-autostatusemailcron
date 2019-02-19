//{block name="backend/order/controller/list" append}
//{namespace name=backend/dpn_cron_status_email/translations}
Ext.define('Shopware.apps.DpnCronStatusMailOrder.controller.List', {
    override: 'Shopware.apps.Order.controller.List',

    showOrderMail: function(mail, record) {
        var me = this, message;

        if (mail.get('isAutoSend') === true) {
            message = '{s name=auto_email_sent}Notification email will be sent automatically{/s}';
            Shopware.Notification.createGrowlMessage(me.snippets.successTitle, message, me.snippets.growlMessage)
        } else {
            me.callOverridden(arguments);
        }
    }
});
//{/block}