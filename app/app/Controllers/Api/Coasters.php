<?php

namespace App\Controllers\Api;

use App\Models\Coaster;
use App\Services\CoasterService;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\BaseResource;
use Config\Services;

class Coasters extends BaseResource
{
    use ResponseTrait;

    public function create(): ResponseInterface
    {
        /** @var CoasterService $coasterService */
        $coasterService = service(Services::COASTER_SERVICE);

        /** @var Coaster $coasterModel */
        $coasterModel = single_service(Services::COASTER_MODEL);

        $data = json_decode($this->request->getBody(), true);

        if (!$coasterModel->validate($data)) {
            return $this->failValidationErrors($coasterModel->getValidationErrors());
        }

        $id = $coasterService->saveCoasterAsync($data);

        return $this->respondCreated(['id' => $id], 'Coaster creation job queued successfully');
    }

    public function update(string $id): ResponseInterface
    {
        /** @var CoasterService $coasterService */
        $coasterService = service(Services::COASTER_SERVICE);

        $data = json_decode($this->request->getBody(), true);

        if (!$coasterService->checkIfCoasterExists($id)) {
            return $this->failNotFound();
        }

        /** @var Coaster $coasterModel */
        $coasterModel = single_service(Services::COASTER_MODEL);

        $coasterModel->setRules(array_map(
            fn (string $rule) => str_replace('required', 'if_exist', $rule),
            array_intersect_key(
                $coasterModel->getRules(),
                array_flip([
                    Coaster::NUMBER_OF_PERSONNEL,
                    Coaster::NUMBER_OF_CUSTOMERS,
                    Coaster::HOURS_FROM,
                    Coaster::HOURS_TO,
                ]),
            )
        ));

        if ($coasterModel->validate($data)) {
            $coasterService->saveCoasterAsync($data, $id);
            $result = $this->respondUpdated(['id' => $id], 'Coaster update job queued successfully');
        } else {
            $result = $this->failValidationErrors($coasterModel->getValidationErrors());
        }

        return $result;
    }
}