/**
 * KiwiCommerce
 *
 * Do not edit or add to this file if you wish to upgrade to newer versions in the future.
 * If you wish to customise this module for your needs.
 * Please contact us https://kiwicommerce.co.uk/contacts.
 *
 * @category   KiwiCommerce
 * @package    KiwiCommerce_CronScheduler
 * @copyright  Copyright (C) 2018 Kiwi Commerce Ltd (https://kiwicommerce.co.uk/)
 * @license    https://kiwicommerce.co.uk/magento2-extension-license/
 */

require(
    [
        'Magento_Ui/js/lib/validation/validator',
        'jquery',
        'mage/url',
        'mage/translate'
    ],
    function (validator, $, urlBuilder) {

        validator.addRule(
            'uniquejobcode',
            function (value) {
                var result = false;
                var linkUrl = urlBuilder.build("cronscheduler/validation/uniquejobcode");

                $.ajax({
                    type:"POST",
                    async: false,
                    url: linkUrl, // script to validate in server side
                    data: {jobcode: value},
                    success: function (data) {
                        result = (data.success == true) ? false : true;
                    }
                });

                return result;
            }
            ,
            $.mage.__('Please enter a valid job code.')
        );

        validator.addRule(
            'classexistance',
            function (value) {
                var result = false;
                var linkUrl = urlBuilder.build("cronscheduler/validation/classexistance");

                $.ajax({
                    type:"POST",
                    async: false,
                    url: linkUrl, // script to validate in server side
                    data: {classpath: value},
                    success: function (data) {
                        result = (data.success == true) ? true : false;
                    }
                });

                return result;
            }
            ,
            $.mage.__('Please enter valid class instance.')
        );

        validator.addRule(
            'methodexistance',
            function (value) {

                var result = false;
                var linkUrl = urlBuilder.build("cronscheduler/validation/methodexistance");
                var classpath = $("input[name=instance]").val();

                $.ajax({
                    type:"POST",
                    async: false,
                    url: linkUrl, // script to validate in server side
                    data: {classpath: classpath,methodname: value},
                    success: function (data) {
                        result = (data.success == true) ? true : false;
                    }
                });

                return result;
            }
            ,
            $.mage.__('Please enter valid class method.')
        );

        validator.addRule(
            'checkexpression',
            function (value) {

                var result = false;
                var linkUrl = urlBuilder.build("cronscheduler/validation/cronexpression");

                $.ajax({
                    type:"POST",
                    async: false,
                    url: linkUrl, // script to validate in server side
                    data: {expression: value},
                    success: function (data) {
                        result = (data.success == true) ? true : false;
                    }
                });

                return result;
            }
            ,
            $.mage.__('Please enter a valid cron expression.')
        );

        $.validator.addMethod(
            'validate-comma-separated-emails',
            function (emaillist) {
                emaillist = emaillist.trim();
                if (emaillist.charAt(0) == ',' || emaillist.charAt(emaillist.length - 1) == ',') {
            return false; }
                var emails = emaillist.split(',');
                var invalidEmails = [];
                for (i = 0; i < emails.length; i++) {
                    var v = emails[i].trim();
                    if (!Validation.get('validate-email').test(v)) {
                        invalidEmails.push(v);
                    }
                }
                if (invalidEmails.length) {
            return false; }
                return true;

            },
            $.mage.__('Please enter a valid email address.')
        );
    }
);