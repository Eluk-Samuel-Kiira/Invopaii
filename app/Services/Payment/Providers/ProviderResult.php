<?php

namespace App\Services\Payment\Providers;

class ProviderResult
{
    public function __construct(
        public readonly bool $success,
        public readonly string $status = 'failed',
        public readonly ?string $providerReference = null,
        public readonly ?string $providerStatus = null,
        public readonly ?string $providerCode = null,
        public readonly ?string $providerMessage = null,
        public readonly ?array $responsePayload = null,
        public readonly ?string $nextActionType = null,
        public readonly ?array $nextAction = null,
        public readonly ?string $failureReason = null,
        public readonly ?string $failureCode = null,
        public readonly ?string $acquirerReference = null,
        public readonly ?string $authorizationCode = null,
    ) {}

    public static function success(
        ?string $providerReference = null,
        ?string $providerStatus = 'succeeded',
        ?string $providerMessage = null,
        ?array $responsePayload = null,
        ?string $acquirerReference = null,
        ?string $authorizationCode = null,
    ): self {
        return new self(
            success: true,
            status: 'succeeded',
            providerReference: $providerReference,
            providerStatus: $providerStatus,
            providerMessage: $providerMessage,
            responsePayload: $responsePayload,
            acquirerReference: $acquirerReference,
            authorizationCode: $authorizationCode,
        );
    }

    public static function pending(
        ?string $providerReference = null,
        ?string $providerStatus = 'pending',
        ?string $providerMessage = null,
        ?array $responsePayload = null,
        ?string $nextActionType = null,
        ?array $nextAction = null,
    ): self {
        return new self(
            success: true,
            status: 'processing',
            providerReference: $providerReference,
            providerStatus: $providerStatus,
            providerMessage: $providerMessage,
            responsePayload: $responsePayload,
            nextActionType: $nextActionType,
            nextAction: $nextAction,
        );
    }

    public static function requiresAction(
        string $nextActionType,
        array $nextAction,
        ?string $providerReference = null,
        ?array $responsePayload = null,
    ): self {
        return new self(
            success: true,
            status: 'requires_action',
            providerReference: $providerReference,
            providerStatus: 'requires_action',
            responsePayload: $responsePayload,
            nextActionType: $nextActionType,
            nextAction: $nextAction,
        );
    }

    public static function failure(
        string $message,
        ?string $code = null,
        ?string $providerReference = null,
        ?array $responsePayload = null,
        ?string $providerStatus = null,
    ): self {
        return new self(
            success: false,
            status: 'failed',
            providerReference: $providerReference,
            providerStatus: $providerStatus,
            providerCode: $code,
            providerMessage: $message,
            responsePayload: $responsePayload,
            failureReason: $message,
            failureCode: $code,
        );
    }
}