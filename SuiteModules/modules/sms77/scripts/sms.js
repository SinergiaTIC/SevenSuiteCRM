$(function() {
    const composeSmsDialog = $('#sms77_sms_form').dialog({
        autoOpen: false,
        modal: true,
    }).on('submit', e => {
        e.preventDefault()

        postSMS()
    })

    const notificationDialog = $('#sms77_notification').dialog({
        autoOpen: false,
        buttons: {
            OK() {
                $(this).dialog('close')
            },
        },
        // modal: true,
    })

    function postSMS() {
        const inputs = composeSmsDialog.serializeArray()
        const getValue = name => inputs.find(input => input.name === name).value

        const data = {
            from: getValue('sms77_from'),
            id: getValue('bean_id'),
            message: getValue('sms77_text'),
            module: getValue('module'),
            number: getValue('sms77_to'),
        }

        return $.ajax({
            data,
            dataType: 'json',
            error(jqXHR, textStatus, errorThrown) {
                SUGAR.ajaxUI.showErrorMessage(errorThrown)
            },
            async success([json, bean]) {
                composeSmsDialog.dialog('close')
                notificationDialog
                    .text(JSON.stringify(json, null, 2))
                    .dialog('open')

                document.getElementById('sms77_sms_history')
                    .insertAdjacentHTML('beforeend', `<div>
                    <span class='suitepicon suitepicon-action-right'></span>
                    ${bean.text}
                    <small>${bean.date_entered}</small>
                </div>`)
            },
            type: 'POST',
            url: '/index.php?entryPoint=sms77',
        })
    }

    window.sms77_suitecrm = {
        openSmsDialog() {
            composeSmsDialog.dialog('open')
        },
    }

    const attr = 'sms-button'
    const triggerClass = 'sms77-send-sms'
    const attachedClass = 'sms77-attached'

    for (const phone of [...document.querySelectorAll('[type=phone]')]) {
        if (phone.getAttribute(attr) !== null) continue

        const to = phone.textContent.trim()

        if (to === '') continue

        const src = '/themes/SuiteP/images/p_icon_email_address_32.png'
        const alt = SUGAR.language.get('Contacts', 'LBL_SMS77_SEND_SMS_VIA')
        phone.insertAdjacentHTML('beforeend',
            `<img alt='${alt}' class='${triggerClass}' data-to='${to}' src='${src}' title='${alt}' />`)
        phone.setAttribute(attr, 'true')
    }

    $(`img.${triggerClass}:not(.${attachedClass})`)
        .addClass(attachedClass)
        .on('click', function() {
            $('#sms77_to').val($(this).data('to'))

            sms77_suitecrm.openSmsDialog()
        })
})