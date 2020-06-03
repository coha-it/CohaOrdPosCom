var copci_eTrueInput;
var copci_placeholder;
var copci_inner;
var copci_innerWithQty;

// If jQuery
if(jQuery && $) {
    copci_init();

    $(document).bind("ajaxSend", function() {
        // You should use "**ajaxStop**" instead of "ajaxComplete" if there are more
        // ongoing requests which are not completed yet
    }).bind("ajaxStop", function() {
        copci_init();
    });
}

function copci_init() {

    $('.coha-ord-pos-com-inner.by-qty').removeClass('init');

    copci_eTrueInput    = $('.coha-ord-pos-com input[name="coha_ord_pos_com"]');
    copci_placeholder   = copci_eTrueInput.attr('placeholder');
    copci_inner         = $('.buybox--form .coha-ord-pos-com-inner');
    copci_innerWithQty  = $('.buybox--form .coha-ord-pos-com-inner.by-qty');

    // Coha Order Pos Com Wrapper exists with By QTY
    if(copci_innerWithQty.length > 0) {
        // Init on Change Select Input
        copci_initQtySelectChanges();

        // INit on Change Faker input
        copci_initFakeInputChange();
    }

    // If Checkout inner
    var eCheckoutProducts = $('.is--ctl-checkout .row--product');
    var eCheckoutInnerWithQty = eCheckoutProducts.find('.coha-ord-pos-com-inner.by-qty');
    copci_initProductRows(eCheckoutProducts); // Init the Product Rows

    // On Change checkout-fake-input
    copci_initCheckoutFakeInputs();
}


// Functions
function copci_initProductRows(eProducts) {
    $(eProducts).each(function (i, e) {
        var eWrapper = $(e);
        var eInner = eWrapper.find('.coha-ord-pos-com-inner.by-qty');

        // Only if eInner exists
        if(eInner.length > 0 && !eInner.hasClass('init')) {
            var eOrigInput = eInner.find('input[data-coha-ord-pos-com="true"]');
            var eOrigPlaceholder = eOrigInput.attr('placeholder');
            var sTexts = eOrigInput.val();
            var aTexts = sTexts.split(', ');
            var eQty = eWrapper.find('select[name="sQuantity"]');
            var sQty = eQty.val();
            var iQty = parseInt(sQty);

            for (var j = 0; j < iQty; j++) {
                var sText = aTexts[j];
                eInner.append(copci_createFakeInput(eOrigPlaceholder));
                eInner.find('.fake-input').eq(j).val(sText);
            }
        }

        eInner.addClass('init');
    });
}

function copci_initQtySelectChanges() {
    var qtySelect = $('.quantity--select');
    qtySelect.on('change', function() {
        copci_onQtyChange(copci_innerWithQty, qtySelect);
    });
    copci_onQtyChange(copci_innerWithQty, qtySelect);
}

function copci_initFakeInputChange() {
    $(document).on('change input click', '.fake-input', function() {
        copci_onFakeInputChange(copci_eTrueInput, $('.fake-input:not(.disabled)'));
    });
}

function copci_initCheckoutFakeInputs() {
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


function copci_checkInput(e) {
    $(e).val($(e).val().replace(/[\,\|\&]/gi, ''));
    return $(e).val();
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
            sText = copci_checkInput(this);

            // Push Content
            aTexts.push(sText);
        }
    });

    var sTexts = aTexts.join('\,\ ');
    eOrigInput.val(sTexts);
}
