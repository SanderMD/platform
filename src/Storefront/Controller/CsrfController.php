<?php declare(strict_types=1);

namespace Shopware\Storefront\Controller;

use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Storefront\Framework\Csrf\CsrfModes;
use Shopware\Storefront\Framework\Csrf\Exception\CsrfNotEnabledException;
use Shopware\Storefront\Framework\Csrf\Exception\CsrfWrongModeException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * @RouteScope(scopes={"storefront"})
 */
class CsrfController extends StorefrontController
{
    /**
     * @var CsrfTokenManagerInterface
     */
    private $csrfTokenManager;

    /**
     * @var bool
     */
    private $csrfEnabled;

    /**
     * @var string
     */
    private $csrfMode;

    public function __construct(CsrfTokenManagerInterface $csrfTokenManager, bool $csrfEnabled, string $csrfMode)
    {
        $this->csrfTokenManager = $csrfTokenManager;
        $this->csrfEnabled = $csrfEnabled;
        $this->csrfMode = $csrfMode;
    }

    /**
     * @Route("/csrf/generate", name="frontend.csrf.generateToken", options={"seo"="false"}, defaults={"csrf_protected"=false}, methods={"POST"})
     */
    public function generateCsrf(Request $request)
    {
        if (!$this->csrfEnabled) {
            throw new CsrfNotEnabledException();
        }

        if ($this->csrfMode !== CsrfModes::MODE_AJAX) {
            throw new CsrfWrongModeException(CsrfModes::MODE_AJAX);
        }

        $token = $this->csrfTokenManager->getToken($request->get('intent'));

        return new JsonResponse(['token' => $token->getValue()]);
    }
}
