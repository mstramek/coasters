<?php

namespace App\Controllers\Api;

use App\Models\Wagon;
use App\Services\CoasterService;
use App\Services\WagonService;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\BaseResource;
use Config\Services;

class Wagons extends BaseResource
{
    use ResponseTrait;

    public function create(string $coasterId): ResponseInterface
    {
        /** @var Wagon $wagonModel */
        $wagonModel = service(Services::WAGON_MODEL);
        /** @var WagonService $wagonService */
        $wagonService = service(Services::WAGON_SERVICE);
        /** @var CoasterService $coasterService */
        $coasterService = service(Services::COASTER_SERVICE);

        $data = json_decode($this->request->getBody(), true);

        if (!$wagonModel->validate($data)) {
            $result = $this->failValidationErrors($wagonModel->getValidationErrors());
        } elseif (!$coasterService->checkIfCoasterExists($coasterId)) {
            $result = $this->failNotFound('Coaster not found');
        } else {
            $wagonId = $wagonService->saveWagonAsync($data, $coasterId);
            $result = $this->respondCreated(
                ['id' => $wagonId, 'coasterId' => $coasterId],
                'Wagon creation job queued successfully'
            );
        };

        return $result;
    }

    public function delete(string $coasterId, string $wagonId): ResponseInterface
    {
        /** @var WagonService $wagonService */
        $wagonService = service(Services::WAGON_SERVICE);

        return $wagonService->deleteWagon($coasterId, $wagonId)
            ? $this->respondDeleted(
                ['coasterId' => $coasterId, 'wagonId' => $wagonId, 'status' => 'deleted']
            )
            : $this->failNotFound('Wagon not found.');
    }
}