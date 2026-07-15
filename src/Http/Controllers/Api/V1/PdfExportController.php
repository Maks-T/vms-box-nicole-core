<?php

declare(strict_types=1);

namespace Nicole\Box\Core\Http\Controllers\Api\V1;

use Illuminate\Routing\Controller;
use Nicole\Box\Core\Models\Order;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

class PdfExportController extends Controller
{
  /**
   * Генерация и отдача PDF-потока в браузер (GET /orders/{code}/pdf)
   */
  public function streamPdf(string $code): \Illuminate\Http\Response|\Illuminate\Contracts\Routing\ResponseFactory
  {
    $payload = $this->getTemplateData($code);
    $template = config('nicole.pdf_template', 'nicole-core::pdf.calculator-report');

    $html = view($template, $payload)->render();

    putenv('HOME=/tmp');

    $chromePath = config('nicole.chrome_path', '/usr/bin/google-chrome');

    $pdfContent = \Spatie\Browsershot\Browsershot::html($html)
      ->noSandbox()
      ->setChromePath($chromePath)
      ->format('A4')
      ->pdf();

    return response($pdfContent)
      ->header('Content-Type', 'application/pdf')
      ->header('Content-Disposition', "inline; filename=\"КП_Заказ_{$payload['order']->code}.pdf\"");
  }

  /**
   * Отдача чистой HTML-версии сметы
   */
  public function viewHtml(string $code): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
  {
    $payload = $this->getTemplateData($code);
    $template = config('nicole.pdf_template', 'nicole-core::pdf.calculator-report');

    return view($template, $payload);
  }

  /**
   * Вспомогательный метод: собирает всю общую часть (заказ + QR-код)
   */
  protected function getTemplateData(string $code): array
  {

    $order = Order::with(['customer', 'status', 'sections', 'manager'])
      ->where('code', $code)
      ->firstOrFail();

    $targetUrl = config('app.url') . "/calculator?orderId=" . $order->id;

    $qrOptions = new QROptions([
      'outputInterface' => \chillerlan\QRCode\Output\QRGdImagePNG::class,
      'eccLevel' => \chillerlan\QRCode\Common\EccLevel::H,
      'scale' => 5,
    ]);

    $qrCodeBase64 = new QRCode($qrOptions)->render($targetUrl);

    return [
      'order' => $order,
      'title' => "КП № " . $order->code,
      'qrCode' => $qrCodeBase64,
    ];
  }

}
