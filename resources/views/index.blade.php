<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>PagSeguro PHP SDK 6.0</title>

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,600" rel="stylesheet">

    <!-- Bootstrap -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css"
        integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"
        integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo=" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js"
        integrity="sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49" crossorigin="anonymous">
    </script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js"
        integrity="sha384-ChfqqxuZUCnJSK3+MXmPNIyE6ZbWh2IMqE241rYiqJxyMiZ6OW/JmZQ5stwEULTy" crossorigin="anonymous">
    </script>

    <!-- PagSeguro js -->
    <script type="text/javascript"
        src="https://stc.sandbox.pagseguro.uol.com.br/pagseguro/api/v2/checkout/pagseguro.directpayment.js">
    </script>

</head>

<body>
    <div class="container">
        <div class="card mt-5">
            <div class="card-body">
                <p>ID: 0002</p>
                <p class="card-title">Taxa de Inscrição</p>
                <p>Quantidade: 01</p>
                <p>Valor: R$ {{ $productAmount }}</p>
            </div>
        </div>

        <h1 class="my-5">Formas de Pagamento:</h1>

        @if(Session::get('linkBoleto'))
        <div class="alert alert-success">
            Seu boleto foi gerado com sucesso! Acesse-o <a target="_blank" href="{{ Session::get('linkBoleto') }}">
                aqui</a>!
        </div>
        @endif

        <ul class="nav nav-tabs mt-5" id="myTab" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="creditCard-tab" data-toggle="tab" href="#creditCard" role="tab"
                    aria-controls="creditCard" aria-selected="true">Cartão de Crédito</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="boleto-tab" data-toggle="tab" href="#boleto" role="tab" aria-controls="boleto"
                    aria-selected="false">Boleto</a>
            </li>
        </ul>
        <div class="tab-content" id="myTabContent">
            <div class="tab-pane my-5 show active" id="creditCard" role="tabpanel" aria-labelledby="creditCard-tab">
                <div id="cartoes" class="d-flex">
                    <h5 class="mr-3">Cartões disponíveis: </h5>
                    {{-- Inserted via jQuery --}}
                </div>
                <form id="formCreditCard" action="" method="POST" name="pagamento-cartao-de-credito">
                    @csrf

                    <input type="hidden" name="cardToken" id="cardToken" />
                    <input type="hidden" name="creditCardSenderHash" id="creditCardSenderHash" />
                    <input type="hidden" name="creditCardPayment" id="creditCardPayment" />

                    <div class="form-group">
                        <label for="cardName">Titular do cartão (como escrito)</label>
                        <input type="text" class="form-control" name="cardName" id="cardName"
                            data-type="cartaoDeCredito">
                    </div>

                    <div class="form-group">
                        <label for="cardNumber">Número do cartão</label>
                        <input type="text" class="form-control" name="cardNumber" id="cardNumber"
                            data-type="cartaoDeCredito">
                    </div>

                    <div class="form-group">
                        <label for="cardBrand">Bandeira do cartão</label>
                        <input type="text" class="form-control" style="text-transform: uppercase" name="cardBrand"
                            id="cardBrand" data-type="cartaoDeCredito">
                    </div>

                    <div class="form-group">
                        <label for="cardExpirationDate">Data de Validade</label>
                        <div class="row">

                            <div class="col-md-6">
                                <select class="form-control" name="cardExpirationMonth" id="cardExpirationMonth">
                                    <option value="12">12</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <select class="form-control" name="cardExpirationYear" id="cardExpirationYear">
                                    <option value="2030">2030</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="installments">Número de parcelas</label>
                        <select class="form-control" name="installments" id="installments">
                            <option value="">Insira o número do cartão</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="cardCvv">Código de Segurança</label>
                        <input type="text" class="form-control" name="cardCvv" id="cardCvv">
                    </div>
                    <div class="form-group" id="bandeiraDoCartao"> </div>

                    <button type="submit" class="btn btn-primary">Pagar</button>
                </form>
            </div>
            <div class="tab-pane my-5" id="boleto" role="tabpanel" aria-labelledby="profile-tab">
                <form id="formBoleto" action="" method="POST" name="pagamento-boleto">
                    @csrf

                    <input type="hidden" name="boletoSenderHash" id="boletoSenderHash" />
                    <input type="hidden" name="boletoPayment" id="boletoPayment" />

                    <h1>Gerar Boleto</h1>

                    <button type="submit" class="btn btn-primary">Pagar</button>
                </form>
            </div>
        </div>



    </div>

    <script type="text/javascript" defer>
        const sessionCode = {!! json_encode($sessionCode) !!};

        PagSeguroDirectPayment.setSessionId(sessionCode);

        PagSeguroDirectPayment.getPaymentMethods({
            amount: {{ json_encode($productAmount) }},
            success: function(response) {
                const cartoes = Object.values(response.paymentMethods.CREDIT_CARD.options);
                const container = $('#cartoes');
                cartoes.forEach(function(cartao, index) {
                    if(cartao.status === 'AVAILABLE') {
                        container.append(
                            '<div class=\'mr-2\'> <img src=\'https://stc.pagseguro.uol.com.br'+ cartao.images.SMALL.path + '\' /> <small> '+ cartao.name +' </small> </div>'
                        );
                    }
                });
            },
            error: function(response) {
                throw new Error(response);
            }
        });

        PagSeguroDirectPayment.onSenderHashReady(function(response){
            if(response.status == 'error') {
                throw new Error(response);
            }
            const hash = response.senderHash; //Hash estará disponível nesta variável.
            $('#creditCardSenderHash').val(hash);
            $('#boletoSenderHash').val(hash);
        });

        function getCardBrand(cardNumber, onSuccess){
            PagSeguroDirectPayment.getBrand({
                cardBin: cardNumber.replace(/\s/g,''),
                success: function(response) {
                    const cardBrand = response.brand.name;
                    onSuccess(cardBrand);
                },
                error: function(response) {
                    throw new Error(response);
                }
            });
        }

        function getCardToken(card, onSuccess) {
            PagSeguroDirectPayment.createCardToken({
                cardNumber: card.cardNumber, // Número do cartão de crédito
                brand: card.cardBrand, // Bandeira do cartão
                cvv: card.cardCvv, // CVV do cartão
                expirationMonth: card.cardExpirationMonth, // Mês da expiração do cartão
                expirationYear: card.cardExpirationYear,
                success: function(response) {
                    const cardToken = response.card.token;
                    onSuccess(cardToken);
                },
                error: function(response) {
                    throw new Error(response);
                }
            });
        }

        function getInstallments(cardBrand, onSuccess) {
            PagSeguroDirectPayment.getInstallments({
                amount: {{ json_encode($productAmount) }},
                maxInstallmentNoInterest: {{ json_encode($noInterestInstallments) }},
                brand: cardBrand,
                success: function(response) {
                    const installments = response.installments;
                    console.log(installments);
                    onSuccess(installments);
                },
                error: function(response) {
                    throw new Error(response);
                }
            })
        }

        const formCreditCard = $('#formCreditCard');
        const cardNumber = $('#cardNumber');
        const creditCard = {};

        function updateCreditCard() {
            let values = formCreditCard.serializeArray();
            values.map(function(x) {
                if(!creditCard[x.name]) {
                    creditCard[x.name] = x.value;
                }
            });
        }

        cardNumber.change(function(e) {
            updateCreditCard();
            getCardBrand(creditCard.cardNumber, function (cardBrand) {
                creditCard.cardBrand = cardBrand;
                $('#cardBrand').attr('readonly', false)
                $('#cardBrand').val(creditCard.cardBrand);
                $('#cardBrand').attr('readonly', true)

                getInstallments(cardBrand, function(installments) {
                    const select = $('#installments');
                    select.empty();
                    installments[cardBrand].forEach(function(installment) {
                        const text = installment.quantity + 'x de R$' + installment.installmentAmount + (installment.interestFree ? ' s/ juros' : ' c/ juros');
                        const option = $('<option></option>').attr('value', installment.quantity).text(text);
                        select.append(option);
                    });
                });
            });
        });

        function togglePaymentMethod() {
            setTimeout(function() {
                if( $('#creditCard-tab').attr('aria-selected') === 'true' ) {
                    $('#creditCardPayment').val('true');
                    $('#boletoPayment').val('false');
                } else {
                    $('#creditCardPayment').val('false');
                    $('#boletoPayment').val('true');
                }
            }, 1);
        }

        $('a').click(togglePaymentMethod);
        togglePaymentMethod();

        const form = $('form');
        form.submit(function(e) {
            e.preventDefault();
            if ($('#creditCardPayment').val() === 'true') {
                updateCreditCard();
                getCardToken(creditCard, function (cardToken) {
                    creditCard.cardToken = cardToken;
                    $('#cardToken').val(creditCard.cardToken);
                    form[0].submit();
                });
            } else {
                form[1].submit();
            }
        });

    </script>
</body>

</html>
