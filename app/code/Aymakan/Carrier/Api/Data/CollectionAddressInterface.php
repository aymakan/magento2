<?php

namespace Aymakan\Carrier\Api\Data;

interface CollectionAddressInterface extends \Magento\Framework\Api\ExtensibleDataInterface
{
    const NAME = 'name';
    const EMAIL = 'email';
    const PHONE = 'phone';
    const ADDRESS = 'address';
    const CITY = 'city';
    const UPDATED_AT = 'updated_at';

    /**
     * Get collectionaddress_id
     * @return string|null
     */
    public function getCollectionAddressId();

    /**
     * Set address_id
     * @param string $addressId
     * @return CollectionAddressInterface
     */
    public function setCollectionAddressId($addressId);

    /**
     * Retrieve existing extension attributes object or create a new one.
     * @return CollectionAddressExtensionInterface|null
     */
    public function getExtensionAttributes();

    /**
     * Set an extension attributes object.
     * @param CollectionAddressExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(
        CollectionAddressExtensionInterface $extensionAttributes
    );

    /**
     * Get created_at
     * @return string|null
     */
    public function getCreatedAt();

    /**
     * Set created_at
     * @param string $createdAt
     * @return CollectionAddressInterface
     */
    public function setCreatedAt($createdAt);

    /**
     * Get name
     * @return string|null
     */
    public function getName();

    /**
     * Set name
     * @param string $name
     * @return CollectionAddressInterface
     */
    public function setName($name);

    /**
     * Get email
     * @return string|null
     */
    public function getEmail();

    /**
     * Set email
     * @param string $email
     * @return CollectionAddressInterface
     */
    public function setEmail($email);

    /**
     * Get phone
     * @return string|null
     */
    public function getPhone();

    /**
     * Set phone
     * @param string $phone
     * @return CollectionAddressInterface
     */
    public function setPhone($phone);

    /**
     * Get address
     * @return string|null
     */
    public function getAddress();

    /**
     * Set address
     * @param string $address
     * @return CollectionAddressInterface
     */
    public function setAddress($address);

    /**
     * Get city
     * @return string|null
     */
    public function getCity();

    /**
     * Set city
     * @param $city
     * @return CollectionAddressInterface
     */
    public function setCity($city);
}
