var copci_eTrueInput = $('.coha-ord-pos-com input[name="coha_ord_pos_com"]');
var copci_placeholder = copci_eTrueInput.attr('placeholder');

function copci_onQtyChange(inner, qtySelect) {
    // Qty Defin
    var qty = parseInt(qtySelect.val());

    // Hide and disable all other fields
    inner.find('.fake-input').addClass('disabled').hide();

    // For loop through every Number of QTY
    for (var i = 0; i < qty; i++) {
        // Try to find Field
        var eField = inner.find('.fake-input').eq(i);
        
        if(eField.length) { // Select existign Fields
            eField.removeClass('disabled').show();
        } else { // Create Feild
            inner.append(copci_createFakeInput(copci_placeholder));
        }
    }

    copci_onFakeInputChange(copci_eTrueInput, $('.fake-input:not(.disabled)'));
}

function copci_createFakeInput(placeholder) {
    return '<input type="text" class="fake-input" placeholder="'+placeholder+'" />';
}

function copci_onFakeInputChange(eOrigInput, eFakeInputs) {
    // Cleare old true input
    copci_eTrueInput.val('');

    // Fill input with new
    var aTexts = [];
    eFakeInputs.each(function(i,e) {
        var eInput = $(e);
        var sText = eInput.val();

        // If Input is Filled
        if(sText && sText != '') {
            // Check all specific disallowed Characters
            $(this).val($(this).val().replace(/[\,\|\&]/gi, ''));

            // Push Content
            aTexts.push(sText);
        }
    });

    var sTexts = aTexts.join('\,\ ');
    eOrigInput.val(sTexts);
}

if(jQuery && $) {
    var inner = $('.buybox--form .coha-ord-pos-com-inner');
    var innerWithQty = $('.buybox--form .coha-ord-pos-com-inner.by-qty');

    // Coha Order Pos Com Wrapper exists with By QTY
    if(innerWithQty.length) {

        // Init on Change Select Input
        var qtySelect = $('.quantity--select');
        qtySelect.on('change', function() {
            copci_onQtyChange(innerWithQty, qtySelect);
        });
        copci_onQtyChange(innerWithQty, qtySelect);

        // INit on Change Faker input
        $(document).on('change input click', '.fake-input', function() {
            copci_onFakeInputChange(copci_eTrueInput, $('.fake-input:not(.disabled)'));
        });
    }

    // If Checkout inner
    var eCheckoutProducts = $('.is--ctl-checkout .row--product');
    var eCheckoutInnerWithQty = $('.is--ctl-checkout .coha-ord-pos-com-inner.by-qty');
    if(eCheckoutInnerWithQty.length) {
        // Init
        $(eCheckoutProducts).each(function (i, e) {
            var eWrapper = $(e);
            var eInner = eWrapper.find('.coha-ord-pos-com-inner.by-qty');
            var eOrigInput = eInner.find('input[data-coha-ord-pos-com="true"]');
            var eOrigPlaceholder = eOrigInput.attr('placeholder');
            var sTexts = eOrigInput.val();
            var aTexts = sTexts.split(', ');
            console.log(aTexts);
            var eQty = eWrapper.find('select[name="sQuantity"]');
            var sQty = eQty.val();
            var iQty = parseInt(sQty);
            console.log('iQty', iQty);

            for (var j = 0; j < iQty; j++) {
                var sText = aTexts[j];
                console.log('set ', sText);
                eInner.append(copci_createFakeInput(eOrigPlaceholder));
                eInner.find('.fake-input').eq(j).val(sText);
            }
        });

        // On Change checkout-fake-input
        $(document).on('change input click keydown', '.is--ctl-checkout .fake-input', function(event) {
            var eParent = $(this).parent('div');
            var eOriginalInput = eParent.find('[data-coha-ord-pos-com="true"]');
            var eFakeInputs = eParent.find('.fake-input');
            copci_onFakeInputChange(eOriginalInput, eFakeInputs);

            // On Enter?
            if (event.which == 13 && !event.shiftKey) {
                eOriginalInput.keydown();
                eOriginalInput.change();
                eOriginalInput.keyup();
            }
        });

    }
}