<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>12.12 Promo</title>

        <!-- Fonts -->
        <link href="https://fonts.googleapis.com/css?family=Nunito:200,600" rel="stylesheet">

        <!-- Styles -->
        <style>
            html, body {
                background-color: #fff;
                color: #636b6f;
                font-family: 'Nunito', sans-serif;
                font-weight: 200;
                height: 100vh;
                margin: 0;
            }

            .full-height {
                height: 100vh;
            }

            .flex-center {
                align-items: center;
                display: flex;
                justify-content: center;
            }

            .position-ref {
                position: relative;
            }

            .top-right {
                position: absolute;
                right: 10px;
                top: 18px;
            }

            .content {
                text-align: center;
            }

            .title {
                font-size: 32px;
            }

            .links > a {
                color: #636b6f;
                padding: 0 25px;
                font-size: 13px;
                font-weight: 600;
                letter-spacing: .1rem;
                text-decoration: none;
                text-transform: uppercase;
            }

            .m-b-md {
                margin-bottom: 30px;
            }
        </style>
    </head>
    <body>
        <div class="flex-center position-ref full-height">
            <div class="content">
                <div class="title m-b-md">
                    12.12 Promo Online Payment
                </div>

                <div>
                <div id="smart-button-container">
                    <div style="text-align: center;">
                        <div style="margin-bottom: 1.25rem;">
                            <p>Select promo to pay</p>
                            <select id="item-options"><option value="12.12 - 1 Room" price="4200">12.12 - 1 Room - 4200 PHP</option><option value="12.12 - 2 Rooms" price="8400">12.12 - 2 Rooms - 8400 PHP</option><option value="12.12 - 3 Rooms" price="12600">12.12 - 3 Rooms - 12600 PHP</option></select>
                            <select style="visibility: hidden" id="quantitySelect"></select>
                            </div>
                        <div id="paypal-button-container"></div>
                        </div>
                        </div>
                        <script src="https://www.paypal.com/sdk/js?client-id={{env('APP_ENV') == 'production' ? env('PAYPAL_PROMO_BUTTON_LIVE') : env('PAYPAL_PROMO_BUTTON_SANDBOX')}}&currency=PHP" data-sdk-integration-source="button-factory"></script>
                        <script>
                        function initPayPalButton() {
                            var shipping = 0;
                            var itemOptions = document.querySelector("#smart-button-container #item-options");
                        var quantity = parseInt();
                        var quantitySelect = document.querySelector("#smart-button-container #quantitySelect");
                        if (!isNaN(quantity)) {
                        quantitySelect.style.visibility = "visible";
                        }
                        var orderDescription = '';
                        if(orderDescription === '') {
                        orderDescription = 'Item';
                        }
                        paypal.Buttons({
                        style: {
                            shape: 'pill',
                            color: 'gold',
                            layout: 'vertical',
                            label: 'pay',
                            
                        },
                        createOrder: function(data, actions) {
                            var selectedItemDescription = itemOptions.options[itemOptions.selectedIndex].value;
                            var selectedItemPrice = parseFloat(itemOptions.options[itemOptions.selectedIndex].getAttribute("price"));
                            var tax = (0 === 0) ? 0 : (selectedItemPrice * (parseFloat(0)/100));
                            if(quantitySelect.options.length > 0) {
                            quantity = parseInt(quantitySelect.options[quantitySelect.selectedIndex].value);
                            } else {
                            quantity = 1;
                            }

                            tax *= quantity;
                            tax = Math.round(tax * 100) / 100;
                            var priceTotal = quantity * selectedItemPrice + parseFloat(shipping) + tax;
                            priceTotal = Math.round(priceTotal * 100) / 100;
                            var itemTotalValue = Math.round((selectedItemPrice * quantity) * 100) / 100;

                            return actions.order.create({
                            purchase_units: [{
                                description: orderDescription,
                                amount: {
                                currency_code: 'PHP',
                                value: priceTotal,
                                breakdown: {
                                    item_total: {
                                    currency_code: 'PHP',
                                    value: itemTotalValue,
                                    },
                                    shipping: {
                                    currency_code: 'PHP',
                                    value: shipping,
                                    },
                                    tax_total: {
                                    currency_code: 'PHP',
                                    value: tax,
                                    }
                                }
                                },
                                items: [{
                                name: selectedItemDescription,
                                unit_amount: {
                                    currency_code: 'PHP',
                                    value: selectedItemPrice,
                                },
                                quantity: quantity
                                }]
                            }]
                            });
                        },
                        onApprove: function(data, actions) {
                            return actions.order.capture().then(function(details) {
                            alert('Transaction completed by ' + details.payer.name.given_name + '!');
                            });
                        },
                        onError: function(err) {
                            console.log(err);
                        },
                        }).render('#paypal-button-container');
                    }
                    initPayPalButton();
                        </script>
                </div>
            </div>
        </div>
    </body>
</html>
