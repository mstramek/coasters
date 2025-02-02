<?php

namespace App\Filters;

use CodeIgniter\Entity\Cast\JsonCast;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;

class CheckIpFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $allowedIps = getenv('ALLOWED_IPS');

        if ($allowedIps) {
            $allowedIpsArray = explode(',', $allowedIps);
            $clientIp = $request->getIPAddress();

            if (!empty($allowedIpsArray) && !in_array($clientIp, $allowedIpsArray)) {
                log_message('error', join(',', $allowedIpsArray));
                return Services::response()
                    ->setStatusCode(ResponseInterface::HTTP_FORBIDDEN, 'Forbidden')
                    ->setBody(JsonCast::set([
                        'message' => sprintf(
                            'Access Denied: Your IP [%s] is not allowed to access this service.',
                            $clientIp
                        ),
                    ]))
                    ->setContentType('text/json');
            }
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // NOP
    }
}
