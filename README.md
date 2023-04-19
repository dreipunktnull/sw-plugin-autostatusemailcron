# Shopware Plugin: Automatic status emails (CRON version)

Using this plugin notification emails for order- and payment status changes that are written directly to the Shopware database by external systems can be sent automatically. A cron job checks for changed status on a regular basis. Manual sending of status emails is deactivated for selected status to avoid duplicates. 

**Notice:** This plugin is designed to work with the API primarily and is not compatible with batch processing via backend.

The plugin is compatible with Shopware version 5.5.0 and greater. It is not compatible with Shopware 6.
