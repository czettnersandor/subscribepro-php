<?php

namespace SubscribePro\Service\PaymentProfile;

use SubscribePro\Exception\EntityInvalidDataException;
use SubscribePro\Exception\InvalidArgumentException;
use SubscribePro\Service\AbstractService;

/**
 * Config options for payment profile service:
 * - instance_name
 *   Specified class must implement \SubscribePro\Service\PaymentProfile\PaymentProfileInterface interface
 *   Default value is \SubscribePro\Service\PaymentProfile\PaymentProfile
 *
 *   @see \SubscribePro\Service\PaymentProfile\PaymentProfileInterface
 *
 * @method \SubscribePro\Service\PaymentProfile\PaymentProfileInterface   retrieveItem($response, $entityName, \SubscribePro\Service\DataInterface $item = null)
 * @method \SubscribePro\Service\PaymentProfile\PaymentProfileInterface[] retrieveItems($response, $entitiesName)
 *
 * @property \SubscribePro\Service\PaymentProfile\PaymentProfileFactory $dataFactory
 */
class PaymentProfileService extends AbstractService
{
    /**
     * Service name
     */
    public const NAME = 'payment_profile';

    public const API_NAME_PROFILE = 'payment_profile';
    public const API_NAME_PROFILES = 'payment_profiles';

    /**
     * @var array
     */
    protected $allowedFilters = [
        PaymentProfileInterface::CUSTOMER_ID,
        PaymentProfileInterface::MAGENTO_CUSTOMER_ID,
        PaymentProfileInterface::CUSTOMER_EMAIL,
        PaymentProfileInterface::PROFILE_TYPE,
        PaymentProfileInterface::PAYMENT_METHOD_TYPE,
        PaymentProfileInterface::PAYMENT_TOKEN,
        PaymentProfileInterface::TRANSACTION_ID,
    ];

    /**
     * @param array $paymentProfileData
     *
     * @return \SubscribePro\Service\PaymentProfile\PaymentProfileInterface
     */
    public function createProfile(array $paymentProfileData = [])
    {
        // This is an alias for createCreditCardProfile to be backwards compatible
        return $this->createCreditCardProfile($paymentProfileData);
    }

    /**
     * @param array $paymentProfileData
     *
     * @return \SubscribePro\Service\PaymentProfile\PaymentProfileInterface
     */
    public function createExternalVaultProfile(array $paymentProfileData = [])
    {
        $paymentProfileData[PaymentProfileInterface::PROFILE_TYPE] = PaymentProfileInterface::TYPE_EXTERNAL_VAULT;

        return $this->dataFactory->create($paymentProfileData);
    }

    /**
     * @param array $paymentProfileData
     *
     * @return \SubscribePro\Service\PaymentProfile\PaymentProfileInterface
     */
    public function createCreditCardProfile(array $paymentProfileData = [])
    {
        $paymentProfileData[PaymentProfileInterface::PROFILE_TYPE] = PaymentProfileInterface::TYPE_SPREEDLY_VAULT;
        $paymentProfileData[PaymentProfileInterface::PAYMENT_METHOD_TYPE] = PaymentProfileInterface::TYPE_CREDIT_CARD;

        return $this->dataFactory->create($paymentProfileData);
    }

    /**
     * @param array $paymentProfileData
     *
     * @return \SubscribePro\Service\PaymentProfile\PaymentProfileInterface
     */
    public function createBankAccountProfile(array $paymentProfileData = [])
    {
        $paymentProfileData[PaymentProfileInterface::PROFILE_TYPE] = PaymentProfileInterface::TYPE_SPREEDLY_VAULT;
        $paymentProfileData[PaymentProfileInterface::PAYMENT_METHOD_TYPE] = PaymentProfileInterface::TYPE_BANK_ACCOUNT;

        return $this->dataFactory->create($paymentProfileData);
    }

    /**
     * @param array $paymentProfileData
     *
     * @return \SubscribePro\Service\PaymentProfile\PaymentProfileInterface
     */
    public function createApplePayProfile(array $paymentProfileData = [])
    {
        $paymentProfileData[PaymentProfileInterface::PROFILE_TYPE] = PaymentProfileInterface::TYPE_SPREEDLY_VAULT;
        $paymentProfileData[PaymentProfileInterface::PAYMENT_METHOD_TYPE] = PaymentProfileInterface::TYPE_APPLE_PAY;

        return $this->dataFactory->create($paymentProfileData);
    }

    /**
     * @param array $paymentProfileData
     *
     * @return \SubscribePro\Service\PaymentProfile\PaymentProfileInterface
     */
    public function createAndroidPayProfile(array $paymentProfileData = [])
    {
        $paymentProfileData[PaymentProfileInterface::PROFILE_TYPE] = PaymentProfileInterface::TYPE_SPREEDLY_VAULT;
        $paymentProfileData[PaymentProfileInterface::PAYMENT_METHOD_TYPE] = PaymentProfileInterface::TYPE_ANDROID_PAY;

        return $this->dataFactory->create($paymentProfileData);
    }

    /*
     * @param array $paymentProfileData
     * @return \SubscribePro\Service\PaymentProfile\PaymentProfileInterface
     */
    public function createThirdPartyVaultProfile(array $paymentProfileData = [])
    {
        $paymentProfileData[PaymentProfileInterface::PROFILE_TYPE] = PaymentProfileInterface::TYPE_SPREEDLY_VAULT;
        $paymentProfileData[PaymentProfileInterface::PAYMENT_METHOD_TYPE] = PaymentProfileInterface::TYPE_THIRD_PARTY_TOKEN;

        return $this->dataFactory->create($paymentProfileData);
    }

    /**
     * @param \SubscribePro\Service\PaymentProfile\PaymentProfileInterface $paymentProfile
     *
     * @return \SubscribePro\Service\PaymentProfile\PaymentProfileInterface
     *
     * @throws \SubscribePro\Exception\EntityInvalidDataException
     * @throws \SubscribePro\Exception\HttpException
     */
    public function saveProfile(PaymentProfileInterface $paymentProfile)
    {
        switch ($paymentProfile->getProfileType()) {
            case PaymentProfileInterface::TYPE_EXTERNAL_VAULT:
                return $this->saveExternalVaultProfile($paymentProfile);

            case PaymentProfileInterface::TYPE_SPREEDLY_DUAL_VAULT:
                throw new EntityInvalidDataException('Unsupported profile type: ' . $paymentProfile->getProfileType());
            case PaymentProfileInterface::TYPE_SPREEDLY_VAULT:
                switch ($paymentProfile->getPaymentMethodType()) {
                    case PaymentProfileInterface::TYPE_BANK_ACCOUNT:
                        return $this->saveBankAccountProfile($paymentProfile);

                    case PaymentProfileInterface::TYPE_THIRD_PARTY_TOKEN:
                        return $this->saveThirdPartyTokenProfile($paymentProfile);

                    case PaymentProfileInterface::TYPE_APPLE_PAY:
                        return $this->saveApplePayProfile($paymentProfile);

                    case PaymentProfileInterface::TYPE_ANDROID_PAY:
                        // Not implemented yet
                        throw new EntityInvalidDataException('Unsupported payment method type: ' . $paymentProfile->getPaymentMethodType());
                    case PaymentProfileInterface::TYPE_CREDIT_CARD:
                    default:
                        // The default is always save credit card profile to be backwards compatible
                        return $this->saveCreditCardProfile($paymentProfile);
                }
        }

        // Default - Assume Spreedly Vault - credit card profile
        // The default is always save credit card profile to be backwards compatible
        return $this->saveCreditCardProfile($paymentProfile);
    }

    private function saveExternalVaultProfile(PaymentProfileInterface $paymentProfile)
    {
        if ($paymentProfile->isNew()) {
            $postData = [self::API_NAME_PROFILE => $paymentProfile->getExternalVaultCreatingFormData()];
            $response = $this->httpClient->post('/services/v2/vault/paymentprofile/external-vault.json', $postData);
        } else {
            $postData = [self::API_NAME_PROFILE => $paymentProfile->getExternalVaultSavingFormData()];
            $response = $this->httpClient->post("/services/v2/vault/paymentprofiles/{$paymentProfile->getId()}.json", $postData);
        }

        return $this->retrieveItem($response, self::API_NAME_PROFILE, $paymentProfile);
    }

    /**
     * @param \SubscribePro\Service\PaymentProfile\PaymentProfileInterface $paymentProfile
     *
     * @return \SubscribePro\Service\PaymentProfile\PaymentProfileInterface
     *
     * @throws \SubscribePro\Exception\EntityInvalidDataException
     * @throws \SubscribePro\Exception\HttpException
     */
    private function saveCreditCardProfile(PaymentProfileInterface $paymentProfile)
    {
        $postData = [self::API_NAME_PROFILE => $paymentProfile->getFormData()];
        if ($paymentProfile->isNew()) {
            $response = $this->httpClient->post('/services/v2/vault/paymentprofile/creditcard.json', $postData);
        } else {
            $response = $this->httpClient->post("/services/v2/vault/paymentprofiles/{$paymentProfile->getId()}.json", $postData);
        }

        return $this->retrieveItem($response, self::API_NAME_PROFILE, $paymentProfile);
    }

    /**
     * @param \SubscribePro\Service\PaymentProfile\PaymentProfileInterface $paymentProfile
     *
     * @return \SubscribePro\Service\PaymentProfile\PaymentProfileInterface
     *
     * @throws \SubscribePro\Exception\EntityInvalidDataException
     * @throws \SubscribePro\Exception\HttpException
     */
    private function saveApplePayProfile(PaymentProfileInterface $paymentProfile)
    {
        if ($paymentProfile->isNew()) {
            $postData = [self::API_NAME_PROFILE => $paymentProfile->getApplePayCreatingFormData()];
            $response = $this->httpClient->post('/services/v2/vault/paymentprofile/applepay.json', $postData);
        } else {
            $postData = [self::API_NAME_PROFILE => $paymentProfile->getApplePaySavingFormData()];
            $response = $this->httpClient->post("/services/v2/vault/paymentprofiles/{$paymentProfile->getId()}.json", $postData);
        }

        return $this->retrieveItem($response, self::API_NAME_PROFILE, $paymentProfile);
    }

    /**
     * @param \SubscribePro\Service\PaymentProfile\PaymentProfileInterface $paymentProfile
     *
     * @return \SubscribePro\Service\PaymentProfile\PaymentProfileInterface
     *
     * @throws \SubscribePro\Exception\EntityInvalidDataException
     * @throws \SubscribePro\Exception\HttpException
     */
    private function saveBankAccountProfile(PaymentProfileInterface $paymentProfile)
    {
        if ($paymentProfile->isNew()) {
            $postData = [self::API_NAME_PROFILE => $paymentProfile->getBankAccountCreatingFormData()];
            $response = $this->httpClient->post('/services/v2/vault/paymentprofile/bankaccount.json', $postData);
        } else {
            $postData = [self::API_NAME_PROFILE => $paymentProfile->getBankAccountSavingFormData()];
            $response = $this->httpClient->post("/services/v2/vault/paymentprofiles/{$paymentProfile->getId()}.json", $postData);
        }

        return $this->retrieveItem($response, self::API_NAME_PROFILE, $paymentProfile);
    }

    /**
     * @param int $paymentProfileId
     *
     * @return \SubscribePro\Service\PaymentProfile\PaymentProfileInterface
     *
     * @throws \SubscribePro\Exception\HttpException
     */
    public function redactProfile($paymentProfileId)
    {
        $response = $this->httpClient->put("/services/v1/vault/paymentprofiles/{$paymentProfileId}/redact.json");

        return $this->retrieveItem($response, self::API_NAME_PROFILE);
    }

    /**
     * @param int $paymentProfileId
     *
     * @return \SubscribePro\Service\PaymentProfile\PaymentProfileInterface
     *
     * @throws \SubscribePro\Exception\HttpException
     */
    public function loadProfile($paymentProfileId)
    {
        $response = $this->httpClient->get("/services/v2/vault/paymentprofiles/{$paymentProfileId}.json");

        return $this->retrieveItem($response, self::API_NAME_PROFILE);
    }

    /**
     * Retrieve an array of all payment profiles.
     *  Available filters:
     * - magento_customer_id
     * - customer_email
     * - profile_type
     * - payment_method_type
     * - transaction_id
     *
     * @param array $filters
     *
     * @return \SubscribePro\Service\PaymentProfile\PaymentProfileInterface[]
     *
     * @throws \SubscribePro\Exception\HttpException
     */
    public function loadProfiles(array $filters = [])
    {
        $invalidFilters = array_diff_key($filters, array_flip($this->allowedFilters));
        if (!empty($invalidFilters)) {
            throw new InvalidArgumentException('Only [' . implode(', ', $this->allowedFilters) . '] query filters are allowed.');
        }

        $response = $this->httpClient->get('/services/v2/vault/paymentprofiles.json', $filters);

        return $this->retrieveItems($response, self::API_NAME_PROFILES);
    }

    /**
     * @param \SubscribePro\Service\PaymentProfile\PaymentProfileInterface $paymentProfile
     *
     * @return \SubscribePro\Service\PaymentProfile\PaymentProfileInterface
     *
     * @throws \SubscribePro\Exception\EntityInvalidDataException
     * @throws \SubscribePro\Exception\HttpException
     */
    public function saveThirdPartyTokenProfile(PaymentProfileInterface $paymentProfile)
    {
        if ($paymentProfile->isNew()) {
            $postData = [self::API_NAME_PROFILE => $paymentProfile->getThirdPartyTokenCreatingFormData()];
            $response = $this->httpClient->post('/services/v2/paymentprofile/third-party-token.json', $postData);
        } else {
            $postData = [self::API_NAME_PROFILE => $paymentProfile->getThirdPartyTokenSavingFormData()];
            $response = $this->httpClient->post("/services/v2/vault/paymentprofiles/{$paymentProfile->getId()}.json", $postData);
        }

        return $this->retrieveItem($response, self::API_NAME_PROFILE, $paymentProfile);
    }

    /**
     * @param string                                                       $token
     * @param \SubscribePro\Service\PaymentProfile\PaymentProfileInterface $paymentProfile
     * @param array|null                                                   $metadata
     *
     * @return \SubscribePro\Service\PaymentProfile\PaymentProfileInterface
     *
     * @throws \SubscribePro\Exception\EntityInvalidDataException
     * @throws \SubscribePro\Exception\HttpException
     */
    public function saveToken($token, PaymentProfileInterface $paymentProfile, $metadata = null)
    {
        $postData = ['payment_profile' => $paymentProfile->getTokenFormData()];
        if (!empty($metadata)) {
            $postData['_meta'] = $metadata;
        }
        $response = $this->httpClient->post("/services/v1/vault/tokens/{$token}/store.json", $postData);

        return $this->retrieveItem($response, self::API_NAME_PROFILE, $paymentProfile);
    }

    /**
     * @param string                                                       $token
     * @param \SubscribePro\Service\PaymentProfile\PaymentProfileInterface $paymentProfile
     *
     * @return \SubscribePro\Service\PaymentProfile\PaymentProfileInterface
     *
     * @throws \SubscribePro\Exception\EntityInvalidDataException
     * @throws \SubscribePro\Exception\HttpException
     */
    public function verifyAndSaveToken($token, PaymentProfileInterface $paymentProfile)
    {
        $response = $this->httpClient->post(
            "/services/v1/vault/tokens/{$token}/verifyandstore.json",
            ['payment_profile' => $paymentProfile->getTokenFormData()]
        );

        return $this->retrieveItem($response, self::API_NAME_PROFILE, $paymentProfile);
    }

    /**
     * @param string $token
     *
     * @return \SubscribePro\Service\PaymentProfile\PaymentProfileInterface
     *
     * @throws \SubscribePro\Exception\HttpException
     */
    public function loadProfileByToken($token)
    {
        $response = $this->httpClient->get("/services/v1/vault/tokens/{$token}/paymentprofile.json");

        return $this->retrieveItem($response, self::API_NAME_PROFILE);
    }
}
