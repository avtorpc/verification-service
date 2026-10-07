<?php

namespace App\Controller\Api;

use App\Application\Registration\Step\Service\CompanyRegistrationService;
use App\Domain\Registration\Step\RegistrationStep;
use App\Shared\Exception\ValidationException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class CompanyRegistrationController extends AbstractController
{
    public function __construct(
        private CompanyRegistrationService $registrationService,
    ) {
    }

    #[Route('/company/start', name: 'registration_company_start', methods: ['POST'])]
    public function start(Request $request): JsonResponse
    {
        $data = $request->toArray();

        $errors = [];
        if (empty($data['legalEntity'] ?? '')) { $errors[] = 'legalEntity is required'; }
        if (empty($data['legalFormName'] ?? '')) { $errors[] = 'legalFormName is required'; }
        if (empty($data['companyName'] ?? '')) { $errors[] = 'companyName is required'; }
        if (empty($data['countryCode'] ?? '')) { $errors[] = 'countryCode is required'; }

        if (!empty($errors)) {
            throw new ValidationException(implode('; ', $errors));
        }

        $companyData = [
            'legal_entity' => $data['legalEntity'],
            'legal_form_name' => $data['legalFormName'],
            'company_name' => $data['companyName'],
            'country_code' => $data['countryCode'],
            'role_code' => $data['roleCode'] ?? null,
            'status' => 'draft',
            'trace_id' => $data['traceId'] ?? null,
            'is_kz_nds_applicable' => $data['isKzNdsApplicable'] ?? null,
            'is_nds_payer' => $data['isNdsPayer'] ?? null,
            'user_uuid' => $data['userUuid'] ?? null,
        ];

        return new JsonResponse($this->registrationService->start($companyData), 201);
    }

    #[Route('/company/step/address', name: 'registration_company_step_address', methods: ['POST'])]
    public function stepAddress(Request $request): JsonResponse
    {
        return $this->handleStep($request, RegistrationStep::ADDRESS);
    }

    #[Route('/company/step/leader', name: 'registration_company_step_leader', methods: ['POST'])]
    public function stepLeader(Request $request): JsonResponse
    {
        return $this->handleStep($request, RegistrationStep::LEADER);
    }

    #[Route('/company/step/contact', name: 'registration_company_step_contact', methods: ['POST'])]
    public function stepContact(Request $request): JsonResponse
    {
        return $this->handleStep($request, RegistrationStep::CONTACT);
    }

    #[Route('/company/step/bank-detail', name: 'registration_company_step_bank_detail', methods: ['POST'])]
    public function stepBankDetail(Request $request): JsonResponse
    {
        return $this->handleStep($request, RegistrationStep::BANK_DETAIL);
    }

    #[Route('/company/finish', name: 'registration_company_finish', methods: ['POST'])]
    public function finish(Request $request): JsonResponse
    {
        $companyId = (int) ($request->toArray()['companyId'] ?? 0);
        if ($companyId <= 0) {
            throw new ValidationException('companyId is required');
        }

        return new JsonResponse($this->registrationService->finish($companyId));
    }

    #[Route('/company/progress', name: 'registration_company_progress', methods: ['GET'])]
    public function getProgress(Request $request): JsonResponse
    {
        $companyId = (int) ($request->query->get('companyId') ?? 0);
        if ($companyId <= 0) {
            throw new ValidationException('companyId is required');
        }

        return new JsonResponse($this->registrationService->getProgress($companyId));
    }

    #[Route('/company/step/address', name: 'registration_company_update_address', methods: ['PUT'])]
    public function updateAddress(Request $request): JsonResponse
    {
        return $this->handleUpdateStep($request, RegistrationStep::ADDRESS);
    }

    #[Route('/company/step/leader', name: 'registration_company_update_leader', methods: ['PUT'])]
    public function updateLeader(Request $request): JsonResponse
    {
        return $this->handleUpdateStep($request, RegistrationStep::LEADER);
    }

    #[Route('/company/step/contact', name: 'registration_company_update_contact', methods: ['PUT'])]
    public function updateContact(Request $request): JsonResponse
    {
        return $this->handleUpdateStep($request, RegistrationStep::CONTACT);
    }

    #[Route('/company/step/bank-detail', name: 'registration_company_update_bank_detail', methods: ['PUT'])]
    public function updateBankDetail(Request $request): JsonResponse
    {
        return $this->handleUpdateStep($request, RegistrationStep::BANK_DETAIL);
    }

    private function handleStep(Request $request, RegistrationStep $step): JsonResponse
    {
        $data = $request->toArray();
        $companyId = (int) ($data['companyId'] ?? 0);
        if ($companyId <= 0) {
            throw new ValidationException('companyId is required');
        }

        unset($data['companyId']);

        return new JsonResponse($this->registrationService->submitStep($companyId, $step, $data));
    }

    private function handleUpdateStep(Request $request, RegistrationStep $step): JsonResponse
    {
        $data = $request->toArray();
        $companyId = (int) ($data['companyId'] ?? 0);
        if ($companyId <= 0) {
            throw new ValidationException('companyId is required');
        }

        unset($data['companyId']);

        return new JsonResponse($this->registrationService->updateStep($companyId, $step, $data));
    }
}
