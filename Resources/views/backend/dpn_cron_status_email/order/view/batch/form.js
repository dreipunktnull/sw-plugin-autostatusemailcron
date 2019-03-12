//{block name="backend/order/view/batch/form" append}
//{namespace name=backend/dpn_cron_status_email/translations}
Ext.define('Shopware.apps.DpnCronStatusMailOrder.view.batch.Form', {
    override: 'Shopware.apps.Order.view.batch.Form',

    initComponent: function () {
        var me = this;
        me.callOverridden(arguments);
        me.forceDisableMailFields();
    },

    enableMailField: function (el) {
        var me = this;
        me.forceDisableMailFields();
    },

    forceDisableMailFields: function () {
        var me = this;
        me.getForm().findField('autoSendMail').setValue(false).disable();
        me.getForm().findField('mode').disable();
        me.getForm().findField('documentType').disable();
        me.getForm().findField('createSingleDocument').disable();
    }

});
//{/block}