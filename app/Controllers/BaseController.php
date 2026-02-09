<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Class BaseController
 *
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 * Extend this class in any new controllers:
 *     class Home extends BaseController
 *
 * For security be sure to declare any new methods as protected or private.
 */
abstract class BaseController extends Controller
{
    /**
     * Instance of the main Request object.
     *
     * @var CLIRequest|IncomingRequest
     */
    protected $request;

    /**
     * An array of helpers to be loaded automatically upon
     * class instantiation. These helpers will be available
     * to all other controllers that extend BaseController.
     *
     * @var list<string>
     */
    protected $helpers = ['url'];

    /**
     * Be sure to declare properties for any property fetch you initialized.
     * The creation of dynamic property is deprecated in PHP 8.2.
     */
    // protected $session;

    /**
     * @return void
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        // Do Not Edit This Line
        parent::initController($request, $response, $logger);

        // Preload any models, libraries, etc, here.

        // E.g.: $this->session = service('session');
    }

    protected function requireMinRole(int $min)
    {
        if (!has_min_role($min)) {
            return redirect()->to('/')->with('error', 'Acesso não permitido!');
        }
        return null;
    }

    protected function toDbDate(?string $v): ?string
    {
        $v = trim((string)$v);
        if ($v === '') return null;

        if (preg_match('#^\d{2}/\d{2}/\d{4}$#', $v)) {
            $d = \DateTime::createFromFormat('d/m/Y', $v);
            return $d && $d->format('d/m/Y') === $v ? $d->format('Y-m-d') : null;
        }

        if (preg_match('#^\d{4}-\d{2}-\d{2}$#', $v)) {
            $d = \DateTime::createFromFormat('Y-m-d', $v);
            return $d ? $d->format('Y-m-d') : null;
        }

        return null;
    }
}
