<?php

namespace app\AppFactory\Management\Revenue;

use think\facade\Db;

trait RevenueOrganizationNameTrait
{
    protected function appendRevenueOrganizationNames($data)
    {
        if (is_object($data) && method_exists($data, 'toArray')) {
            $data = $data->toArray();
        }
        if (!is_array($data)) {
            return $data;
        }

        $organizationIds = [];
        $this->collectRevenueOrganizationIds($data, $organizationIds);
        if (!$organizationIds) {
            return $data;
        }

        $organizationNames = Db::name('auth_organization')
            ->whereIn('ao_id', array_keys($organizationIds))
            ->column('organization_name', 'ao_id');

        return $this->fillRevenueOrganizationNames($data, $organizationNames);
    }

    protected function collectRevenueOrganizationIds(array $data, array &$organizationIds)
    {
        foreach ($data as $field => $value) {
            if (in_array($field, ['ao_id', 'payer_ao_id', 'receiver_ao_id'], true)
                && intval($value) > 0) {
                $organizationIds[intval($value)] = true;
            }
            if (is_array($value)) {
                $this->collectRevenueOrganizationIds($value, $organizationIds);
            }
        }
    }

    protected function fillRevenueOrganizationNames(array $data, array $organizationNames)
    {
        $nameFields = [
            'ao_id' => 'organization_name',
            'payer_ao_id' => 'payer_organization_name',
            'receiver_ao_id' => 'receiver_organization_name',
        ];
        foreach ($data as $field => &$value) {
            if (isset($nameFields[$field])) {
                $data[$nameFields[$field]] = $organizationNames[intval($value)] ?? '';
            }
            if (is_array($value)) {
                $value = $this->fillRevenueOrganizationNames($value, $organizationNames);
            }
        }
        unset($value);
        return $data;
    }
}
