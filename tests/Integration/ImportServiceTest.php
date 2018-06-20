<?php

namespace oat\OneRoster\Tests\Integration\Service;

use Doctrine\Common\Collections\ArrayCollection;
use oat\OneRoster\Entity\ClassRoom;
use oat\OneRoster\Entity\Enrollment;
use oat\OneRoster\Entity\EntityRepository;
use oat\OneRoster\Entity\Organisation;
use oat\OneRoster\Entity\RelationConfig;
use oat\OneRoster\Entity\User;
use oat\OneRoster\File\FileHandler;
use oat\OneRoster\Service\ImportService;
use oat\OneRoster\Storage\InMemoryStorage;
use PHPUnit\Framework\TestCase;

class ImportServiceTest extends TestCase
{
    /**
     * @throws \Exception
     */
    public function testImport()
    {
        $fileHandler = new FileHandler();
        $importService = new ImportService($fileHandler);
        $results = $importService->importMultiple(__DIR__ . '/../../data/samples/oneRoster1.0/');

        $entityRepo    = $this->buildEntityRepository($results, $fileHandler);
        $organisations = $entityRepo->getAll(Organisation::class);
        $this->assertOrganisationCollection($organisations);

        /** @var Organisation $oneOrg */
        $oneOrg = $entityRepo->get('12345', Organisation::class);
        $this->assertInstanceOf(Organisation::class, $oneOrg);
        $this->assertSame('12345', $oneOrg->getId());
        $this->assertInternalType('array', $oneOrg->getData());

        $this->assertCount(2, $oneOrg->getEnrollments());
        $this->assertCount(2, $oneOrg->getClasses());
        $this->assertCount(1, $oneOrg->getUsers());

        /** @var ClassRoom $class */
        $class = $oneOrg->getClasses()->first();
        $this->assertSame('class1', $class->getId());
        $this->assertCount(1, $class->getOrgs());
        $this->assertCount(1, $class->getEnrollments());

        /** @var User $user */
        $user = $oneOrg->getUsers()->first();
        $this->assertSame('user1', $user->getId());
        $this->assertCount(1, $user->getOrgs());
        $this->assertCount(2, $user->getEnrollments());

        /** @var Enrollment $enroll */
        $enroll = $oneOrg->getEnrollments()->first();
        $this->assertSame('enrol1', $enroll->getId());
        $this->assertCount(1, $enroll->getUsers());
        $this->assertCount(1, $enroll->getClasses());
        $this->assertCount(1, $enroll->getOrgs());
    }

    protected function assertOrganisationCollection($organisations)
    {
        $this->assertInstanceOf(ArrayCollection::class, $organisations);

        foreach ($organisations as $organisation){
            $this->assertInstanceOf(Organisation::class, $organisation);
        }
    }

    protected function buildEntityRepository($results, $fileHandler)
    {
        $storage          = new InMemoryStorage($results);
        $pathToSchemaJson = __DIR__ . '/../../config/v1/relations.json';
        $dataConfig       = json_decode($fileHandler->getContents($pathToSchemaJson), true);
        $relationConfig   = new RelationConfig($dataConfig);
        $entityRepository = new EntityRepository($storage, $relationConfig);

        return $entityRepository;
    }
}