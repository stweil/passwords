<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Migration;

use OCA\Passwords\Services\Object\AbstractService;
use OCA\Passwords\Services\Object\FolderRevisionService;
use OCA\Passwords\Services\Object\FolderService;
use OCA\Passwords\Services\Object\PasswordRevisionService;
use OCA\Passwords\Services\Object\PasswordService;
use OCA\Passwords\Services\Object\ShareService;
use OCA\Passwords\Services\Object\TagRevisionService;
use OCA\Passwords\Services\Object\TagService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

/**
 * Class DuplicateDeletionHelper
 *
 * @package OCA\Passwords\Migration\DatabaseRepair
 */
class DuplicateDeletionHelper implements IRepairStep {

    /**
     * @var TagService
     */
    protected $tagService;
    /**
     * @var ShareService
     */
    protected $shareService;
    /**
     * @var FolderService
     */
    protected $folderService;
    /**
     * @var PasswordService
     */
    protected $passwordService;
    /**
     * @var TagRevisionService
     */
    protected $tagRevisionService;
    /**
     * @var FolderRevisionService
     */
    protected $folderRevisionService;
    /**
     * @var PasswordRevisionService
     */
    protected $passwordRevisionService;

    /**
     * DuplicateDeletionHelper constructor.
     *
     * @param TagService              $tagService
     * @param ShareService            $shareService
     * @param FolderService           $folderService
     * @param PasswordService         $passwordService
     * @param TagRevisionService      $tagRevisionService
     * @param FolderRevisionService   $folderRevisionService
     * @param PasswordRevisionService $passwordRevisionService
     */
    public function __construct(
        TagService $tagService,
        ShareService $shareService,
        FolderService $folderService,
        PasswordService $passwordService,
        TagRevisionService $tagRevisionService,
        FolderRevisionService $folderRevisionService,
        PasswordRevisionService $passwordRevisionService
    ) {
        $this->tagService              = $tagService;
        $this->shareService            = $shareService;
        $this->folderService           = $folderService;
        $this->passwordService         = $passwordService;
        $this->tagRevisionService      = $tagRevisionService;
        $this->folderRevisionService   = $folderRevisionService;
        $this->passwordRevisionService = $passwordRevisionService;
    }

    /**
     * Returns the step's name
     *
     * @return string
     * @since 9.1.0
     */
    public function getName() {
        return 'Duplicate Object Deletion';
    }

    /**
     * Run repair step.
     * Must throw exception on error.
     *
     * @param IOutput $output
     *
     * @throws \Exception in case of failure
     * @since 9.1.0
     */
    public function run(IOutput $output) {
        $this->deleteDuplicates($this->tagService);
        $this->deleteDuplicates($this->shareService);
        $this->deleteDuplicates($this->folderService);
        $this->deleteDuplicates($this->passwordService);
        $this->deleteDuplicates($this->tagRevisionService);
        $this->deleteDuplicates($this->folderRevisionService);
        $this->deleteDuplicates($this->passwordRevisionService);
    }

    /**
     * @param AbstractService $objectService
     */
    protected function deleteDuplicates(AbstractService $objectService) {
        /** @var \OCA\Passwords\Db\RevisionInterface[] $allDeleted */
        $allDeleted = $objectService->findDeleted();
        $knownUuids = [];

        foreach ($allDeleted as $entry) {
            try {
                $uuid = $entry->getUuid();
                if(isset($knownUuids[ $uuid ])) {
                    $objectService->destroy($knownUuids[ $uuid ]);
                    $knownUuids[ $uuid ] = $entry;
                    continue;
                }
                try {
                    $objectService->findByUuid($uuid);
                    $objectService->destroy($entry);
                } catch (DoesNotExistException | MultipleObjectsReturnedException $e) {
                    $knownUuids[ $uuid ] = $entry;
                }
            } catch (\Exception $e) {

            }
        }
    }
}