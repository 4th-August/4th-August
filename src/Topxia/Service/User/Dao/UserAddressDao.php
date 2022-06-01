<?php

namespace Topxia\Service\User\Dao;

interface UserAddressDao
{
	public function getAddress($id);

    public function getAddressByUserId($userId);

	public function addAddress($address);

	public function updateAddress($id, $address);

    public function deleteAddress($id);

    public function dropFieldData($fieldName);

    public function searchProfiles($conditions, $orderBy, $start, $limit);

	public function searchProfileCount($conditions);
}