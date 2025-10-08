<?php

namespace App\Service;

use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use MicrosoftAzure\Storage\Blob\Models\CreateBlockBlobOptions;
use MicrosoftAzure\Storage\Common\Exceptions\ServiceException;

class AzureBlobService
{
    private $blobClient;
    private $container;
    private $account;

    public function __construct(string $account, string $key, string $container)
    {


        $this->account = $account;
        $this->container = $container;

        $connectionString = "DefaultEndpointsProtocol=https;AccountName={$account};AccountKey={$key}";
        $this->blobClient = BlobRestProxy::createBlobService($connectionString);
    }

    /**
     * Upload une image dans Azure Blob Storage
     */
    public function uploadImage(string $filePath, string $blobName): string
    {
        $content = fopen($filePath, 'r');
        $options = new CreateBlockBlobOptions();

        $this->blobClient->createBlockBlob($this->container, $blobName, $content, $options);

        return sprintf(
            'https://%s.blob.core.windows.net/%s/%s',
            $this->account,
            $this->container,
            $blobName
        );
    }

    /**
     * Supprime une image d’Azure Blob Storage
     */
    public function deleteImage(string $imageUrl): bool
    {
        // Vérifie que l’URL appartient bien à ce compte/container
        if (empty($imageUrl) || !str_contains($imageUrl, $this->account . '.blob.core.windows.net')) {
            return false;
        }

        // Extrait le nom du blob depuis l’URL complète
        $parts = parse_url($imageUrl);
        $blobName = ltrim($parts['path'] ?? '', '/');
        $blobName = preg_replace("#^{$this->container}/#", '', $blobName);

        try {
            $this->blobClient->deleteBlob($this->container, $blobName);
            return true;
        } catch (ServiceException $e) {
            // Optionnel : log en cas d’erreur
            error_log('Erreur suppression Azure Blob : ' . $e->getMessage());
            return false;
        }
    }
}
