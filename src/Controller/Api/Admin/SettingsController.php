<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin;

use App\Controller\Api\AbstractApiCrudController;
use App\Doctrine\ReloadableEntityManagerInterface;
use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use OpenApi\Annotations as OA;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @OA\Get(path="/admin/settings",
 *   operationId="getSettings",
 *   tags={"Administration: Settings"},
 *   description="List the current values of all editable system settings.",
 *   @OA\Response(response=200, description="Success",
 *     @OA\JsonContent(ref="#/components/schemas/Settings")
 *   ),
 *   @OA\Response(response=403, description="Access denied"),
 *   security={{"api_key": {}}},
 * )
 *
 * @OA\Put(path="/admin/settings",
 *   operationId="editSettings",
 *   tags={"Administration: Settings"},
 *   description="Update settings to modify any settings provided.",
 *   @OA\RequestBody(
 *     @OA\JsonContent(ref="#/components/schemas/Settings")
 *   ),
 *   @OA\Response(response=200, description="Success",
 *     @OA\JsonContent(ref="#/components/schemas/Api_Status")
 *   ),
 *   @OA\Response(response=403, description="Access denied"),
 *   security={{"api_key": {}}},
 * )
 *
 * @extends AbstractApiCrudController<Entity\Settings>
 */
class SettingsController extends AbstractApiCrudController
{
    protected string $entityClass = Entity\Settings::class;

    public function __construct(
        protected Entity\Repository\SettingsRepository $settingsRepo,
        ReloadableEntityManagerInterface $em,
        Serializer $serializer,
        ValidatorInterface $validator
    ) {
        parent::__construct($em, $serializer, $validator);
    }

    public function listAction(
        ServerRequest $request,
        Response $response,
        ?string $group = null
    ): ResponseInterface {
        $context = [];
        if (null !== $group && in_array($group, Entity\Settings::VALID_GROUPS, true)) {
            $context[AbstractNormalizer::GROUPS] = [$group];
        }

        $settings = $this->settingsRepo->readSettings();
        return $response->withJson($this->toArray($settings, $context));
    }

    public function updateAction(
        ServerRequest $request,
        Response $response,
        ?string $group = null
    ): ResponseInterface {
        $context = [];
        if (null !== $group && in_array($group, Entity\Settings::VALID_GROUPS, true)) {
            $context[AbstractNormalizer::GROUPS] = [$group];
        }

        $settings = $this->settingsRepo->readSettings();

        if ($group === Entity\Settings::GROUP_GENERAL && !$settings->isSetupComplete()) {
            $settings->updateSetupComplete();
        }

        $this->editRecord((array)$request->getParsedBody(), $settings, $context);

        return $response->withJson(Entity\Api\Status::success());
    }
}
