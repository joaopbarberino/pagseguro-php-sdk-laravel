<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

use PagSeguro\Library;
use PagSeguro\Configuration\Configure;
use PagSeguro\Domains\Requests\Payment;
use PagSeguro\Domains\Requests\Authorization;
use PagSeguro\Enum\Shipping;
use PagSeguro\Enum\Authorization\Permissions;
use PagSeguro\Services\Session;
use PagSeguro\Services\Installment;
use PagSeguro\Domains\Requests\Requests;
use PagSeguro\Domains\Requests\DirectPayment\CreditCard;
use PagSeguro\Domains\Requests\DirectPayment\Boleto;

class IndexController extends Controller
{
    private $noInterestInstallments = 2;

    public function __construct()
    {
        // Initialize library
        try {
            Library::initialize();
        } catch (Exception $e) {
            dd($e, 'Initialize');
        }
        Library::cmsVersion()->setName(env('APP_NAME'))->setRelease(env('APP_VERSION'));
        Library::moduleVersion()->setName(env('APP_NAME'))->setRelease(env('APP_VERSION'));

        // Set up lib variables
        Configure::setEnvironment('sandbox'); //production or sandbox
        Configure::setAccountCredentials(
            env('PAGSEGURO_EMAIL'),
            env('PAGSEGURO_TOKEN')
        );
        Configure::setApplicationCredentials(
            env('PAGSEGURO_APP_ID'),
            env('PAGSEGURO_APP_KEY')
        );
        Configure::setCharset('UTF-8'); // UTF-8 or ISO-8859-1
        // If it will generate logs and where
        Configure::setLog(false, 'logsPagSeguro/log.log');

        $this->sessionCode = $this->getSessionCode();
        $this->productAmount = 5000.00;

        /*
        $this->middleware(function ($request, $next) {
            if (!session()->get('pagseguro-session-code')) {
                session()->put('pagseguro-session-code', $this->getSessionCode());
            }

            $this->sessionCode = session()->get('pagseguro-session-code');

            return $next($request);
        });
        */
        // Get lib auth
        // $authorization = new Authorization();

        // $authorization->setReference('AUTH_LIB_PHP_0001');
        // $authorization->setRedirectUrl(env('APP_URL'));
        // $authorization->setNotificationUrl(env('APP_URL') . '/notificacao');

        // $authorization->addPermission(Permissions::CREATE_CHECKOUTS);
        // $authorization->addPermission(Permissions::SEARCH_TRANSACTIONS);
        // $authorization->addPermission(Permissions::RECEIVE_TRANSACTION_NOTIFICATIONS);
        // $authorization->addPermission(Permissions::MANAGE_PAYMENT_PRE_APPROVALS);
        // $authorization->addPermission(Permissions::DIRECT_PAYMENT);
        // Configure::getApplicationCredentials()->setAuthorizationCode('FD3AF1B214EC40F0B0A6745D041BF50D');
        // try {
        //     $response = $authorization->register(
        //         Configure::getApplicationCredentials()
        //     );
        //     echo "<h2>Criando requisi&ccedil;&atilde;o de authorização</h2>"
        //         . "<p>URL do pagamento: <strong>$response</strong></p>"
        //         . "<p><a title=\"URL de Autorização\" href=\"$response\" target=\_blank\">"
        //         . "Ir para URL de authorização.</a></p>";
        // } catch (Exception $e) {
        //     dd($e, 'Auth');
        // }
    }

    public function index()
    {
        $sessionCode = $this->sessionCode;
        $productAmount = $this->productAmount;
        $noInterestInstallments = $this->noInterestInstallments;

        return view('index', compact('sessionCode', 'productAmount', 'noInterestInstallments'));
    }

    public function store(Request $request)
    {
        if ($request->creditCardPayment) {
            $this->creditCardPayment($request->all());
        } elseif ($request->boletoPayment) {
            return $this->boletoPayment($request->all());
        }
    }

    /**
     * @param CreditCard|Boleto $object
     * @param array $data
     */
    private function createSelling(Requests $object = null, array $data)
    {
        $object->setReceiverEmail(env('PAGSEGURO_RECEIVER_EMAIL'));

        // Set a reference code for this payment request. It is useful to identify this payment
        // in future notifications.
        $object->setReference('PAGAMENTO-' . ($object instanceof CreditCard ? 'CARTAO' : 'BOLETO'));
        $object->setCurrency('BRL');

        // Add an item to the payment request
        $object->addItems()->withParameters(
            '0002', // ID
            'TAXA DE INSCRIÇÃO', // Name
            1, // Quantity
            $this->productAmount // Amount
        );

        // Set customer information
        $object->setSender()->setName('ESTUDANTE NOME');
        $object->setSender()->setEmail('estudante.email@sandbox.pagseguro.com.br');
        $object->setSender()->setPhone()->withParameters(
            11, // Area Code
            999999999
        );
        $object->setSender()->setDocument()->withParameters(
            'CPF',
            '10173649076'
        );

        $hash = isset($data['creditCardSenderHash']) ? $data['creditCardSenderHash'] : $data['boletoSenderHash'];
        $object->setSender()->setHash($hash);
        $object->setSender()->setIp(request()->ip());

        // There is no shipping
        $object->setShipping()->setAddressRequired()->withParameters('FALSE');

        // Set the Payment Mode for this payment request
        $object->setMode('DEFAULT');

        return $object;
    }

    private function creditCardPayment($data)
    {
        // Instantiate a new direct payment request, using Credit Card
        $creditCard = $this->createSelling(new CreditCard, $data);

        // Set billing information for credit card
        $creditCard->setBilling()->setAddress()->withParameters(
            'Av. Paulista',
            '1578',
            'Bela Vista',
            '01310-200',
            'São Paulo',
            'SP',
            'BRA',
            'Museu'
        );

        $creditCard->setToken($data['cardToken']);

        // Set the installment quantity and value (could be obtained using the Installments
        // service, that have an example here in \public\getInstallments.php)
        $availableInstallments = $this->getInstallments($data['cardBrand']);

        $choosenInstallment = Arr::where($availableInstallments, function ($installment) use ($data) {
            return $installment->getQuantity() == $data['installments'];
        });
        $choosenInstallment = Arr::first($choosenInstallment);

        $creditCard->setInstallment()->withParameters(
            $choosenInstallment->getQuantity(),
            $choosenInstallment->getAmount(),
            $this->noInterestInstallments
        );

        // Set credit card holder information
        $creditCard->setHolder()->setBirthdate('01/01/2000');
        $creditCard->setHolder()->setName($data['cardName']); // Equals in Credit Card
        $creditCard->setHolder()->setPhone()->withParameters(
            11,
            999999999
        );
        $creditCard->setHolder()->setDocument()->withParameters(
            'CPF',
            '10173649076'
        );

        try {
            // Get the crendentials and register the boleto payment
            $result = $creditCard->register(
                Configure::getAccountCredentials()
            );
            // code
            // grossAmount
            // netAmount
            // $pagamento = Pagamentos::create();
            // $pagamento->codigo_transacao = $result->getCode();
            // $pagamento->valor = $result->getGrossAmount();
            // $pagamento->valor_pagseguro = $result->getNetAmount();
            dd($result);
        } catch (Exception $e) {
            dd($e, 'Credit Card');
        }
    }

    private function boletoPayment($data)
    {
        $boleto = $this->createSelling(new Boleto, $data);

        try {
            // Get the crendentials and register the boleto payment
            $result = $boleto->register(
                Configure::getAccountCredentials()
            );

            // You can use methods like getCode() to get the transaction code and getPaymentLink() for the Payment's URL
            $linkBoleto = $result->getPaymentLink();
            return redirect('/')->with('linkBoleto', $linkBoleto);
        } catch (Exception $e) {
            dd($e->getMessage(), 'Boleto');
        }
    }

    private function getSessionCode()
    {
        try {
            $sessionCode = Session::create(
                Configure::getAccountCredentials()
            );
        } catch (Exception $e) {
            dd($e->getMessage(), 'Session');
        }

        return $sessionCode->getResult();
    }

    private function getInstallments($cardBrand = null)
    {
        $options = [
            'amount' => $this->productAmount, //Required
            'card_brand' => $cardBrand, //Optional
            'max_installment_no_interest' => $this->noInterestInstallments //Optional
        ];

        try {
            $result = Installment::create(
                Configure::getAccountCredentials(),
                $options
            );
            return $result->getInstallments();
        } catch (Exception $e) {
            dd($e->getMessage(), 'getInstallments');
        }
    }
}
