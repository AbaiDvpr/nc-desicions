<?php declare(strict_types=1);

namespace App\Http\Controllers\Backend\Modules\Client;

use App\Http\Controllers\Backend\BaseController;
use App\Http\Requests\Backend\Modules\Client\BalanceRequest;
use App\Services\Payment\PaymentService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Models\User;

class PaymentController extends BaseController
{

    /**
     * @var Builder
     */
    protected $client;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var PaymentService
     */
    private $paymentService;

    /**
     * IndexController constructor.
     *
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        parent::__construct();

        $this->request        = $request;
        if ($request->user) {
            $this->client         = User::findOrFail((int) $request->user ?? 0);
            $this->paymentService = new PaymentService($this->client);
        }

        view()->share([
            'client' => $this->client
        ]);
    }

    /**
     * @return View
     */
    public function index(): View
    {
        $payments = $this->client->balanceHistories()->latest()->paginate(30);

        return view('backend.modules.client.payments.index', compact('payments'));
    }

    /**
     * Форма для пополнения баланса
     *
     * @return View
     */
    public function upPaymentForm(): View
    {
        return view('backend.modules.client.payments.payment-form', [
            'pageTitle' => 'пополнения баланса',
            'route'     => route('backend.modules.client.payment.toUpBalance', ['user' => $this->client->id])
        ]);
    }

    /**
     * Пополнение баланса пользователя
     *
     * @param  BalanceRequest                               $request
     * @return RedirectResponse
     * @throws \Telegram\Bot\Exceptions\TelegramSDKException
     */
    public function toUpBalance(BalanceRequest $request): RedirectResponse
    {
        $balance = (float) $request->input('balance');
        $comment = $request->input('comment');
        $status  = $this->paymentService->changeBalance($balance, $comment);

        if ($status['code'] === PaymentService::SUCCESS_CODE) {
            return redirect()->route('backend.modules.client.payment.index', [
                'user' => $this->client->id
            ])->with(['success' => $status['message']]);
        }

        return redirect()->back()->withErrors(['errors' => $status['message']]);
    }

    /**
     * Форма для списания с баланса
     *
     * @return View
     */
    public function downPaymentForm(): View
    {
        return view('backend.modules.client.payments.payment-form', [
            'pageTitle' => 'списание баланса',
            'route'     => route('backend.modules.client.payment.toDownBalance', ['user' => $this->client->id])
        ]);
    }

    /**
     * Пополнение баланса пользователя
     *
     * @param  BalanceRequest                               $request
     * @return RedirectResponse
     * @throws \Telegram\Bot\Exceptions\TelegramSDKException
     */
    public function toDownBalance(BalanceRequest $request): RedirectResponse
    {
        $balance = (float) $request->input('balance');
        $comment = $request->input('comment');
        $status  = $this->paymentService->changeBalance($balance, $comment, false);

        if ($status['code'] === PaymentService::SUCCESS_CODE) {
            return redirect()->route('backend.modules.client.payment.index', [
                'user' => $this->client->id
            ])->with(['success' => $status['message']]);
        }

        return redirect()->back()->withErrors(['errors' => $status['message']]);
    }
}
