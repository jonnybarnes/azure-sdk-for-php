<?php
namespace Microsoft\Azure\Management\DevTestLabs;
final class VirtualNetworks
{
    /**
     * @param \Microsoft\Rest\ClientInterface $_client
     */
    public function __construct(\Microsoft\Rest\ClientInterface $_client)
    {
        $this->_List_operation = $_client->createOperation('VirtualNetworks_List');
        $this->_Get_operation = $_client->createOperation('VirtualNetworks_Get');
        $this->_CreateOrUpdate_operation = $_client->createOperation('VirtualNetworks_CreateOrUpdate');
        $this->_Delete_operation = $_client->createOperation('VirtualNetworks_Delete');
        $this->_Update_operation = $_client->createOperation('VirtualNetworks_Update');
    }
    /**
     * List virtual networks in a given lab.
     * @param string $resourceGroupName
     * @param string $labName
     * @param string|null $_expand
     * @param string|null $_filter
     * @param integer|null $_top
     * @param string|null $_orderby
     * @return array
     */
    public function list_(
        $resourceGroupName,
        $labName,
        $_expand = null,
        $_filter = null,
        $_top = null,
        $_orderby = null
    )
    {
        return $this->_List_operation->call([
            'resourceGroupName' => $resourceGroupName,
            'labName' => $labName,
            '$expand' => $_expand,
            '$filter' => $_filter,
            '$top' => $_top,
            '$orderby' => $_orderby
        ]);
    }
    /**
     * Get virtual network.
     * @param string $resourceGroupName
     * @param string $labName
     * @param string $name
     * @param string|null $_expand
     * @return array
     */
    public function get(
        $resourceGroupName,
        $labName,
        $name,
        $_expand = null
    )
    {
        return $this->_Get_operation->call([
            'resourceGroupName' => $resourceGroupName,
            'labName' => $labName,
            'name' => $name,
            '$expand' => $_expand
        ]);
    }
    /**
     * Create or replace an existing virtual network. This operation can take a while to complete.
     * @param string $resourceGroupName
     * @param string $labName
     * @param string $name
     * @param array $virtualNetwork
     * @return array
     */
    public function createOrUpdate(
        $resourceGroupName,
        $labName,
        $name,
        array $virtualNetwork
    )
    {
        return $this->_CreateOrUpdate_operation->call([
            'resourceGroupName' => $resourceGroupName,
            'labName' => $labName,
            'name' => $name,
            'virtualNetwork' => $virtualNetwork
        ]);
    }
    /**
     * Delete virtual network. This operation can take a while to complete.
     * @param string $resourceGroupName
     * @param string $labName
     * @param string $name
     */
    public function delete(
        $resourceGroupName,
        $labName,
        $name
    )
    {
        return $this->_Delete_operation->call([
            'resourceGroupName' => $resourceGroupName,
            'labName' => $labName,
            'name' => $name
        ]);
    }
    /**
     * Modify properties of virtual networks.
     * @param string $resourceGroupName
     * @param string $labName
     * @param string $name
     * @param array $virtualNetwork
     * @return array
     */
    public function update(
        $resourceGroupName,
        $labName,
        $name,
        array $virtualNetwork
    )
    {
        return $this->_Update_operation->call([
            'resourceGroupName' => $resourceGroupName,
            'labName' => $labName,
            'name' => $name,
            'virtualNetwork' => $virtualNetwork
        ]);
    }
    /**
     * @var \Microsoft\Rest\OperationInterface
     */
    private $_List_operation;
    /**
     * @var \Microsoft\Rest\OperationInterface
     */
    private $_Get_operation;
    /**
     * @var \Microsoft\Rest\OperationInterface
     */
    private $_CreateOrUpdate_operation;
    /**
     * @var \Microsoft\Rest\OperationInterface
     */
    private $_Delete_operation;
    /**
     * @var \Microsoft\Rest\OperationInterface
     */
    private $_Update_operation;
}