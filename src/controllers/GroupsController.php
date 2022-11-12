<?php

namespace venveo\redirect\controllers;

use craft\web\Controller;
use venveo\redirect\models\Group;
use venveo\redirect\Plugin;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\Response as YiiResponse;

class GroupsController extends Controller
{
    /**
     * @inheritdoc
     */
    public function beforeAction($action): bool
    {
        $this->requirePermission(Plugin::PERMISSION_MANAGE_GROUPS);

        return parent::beforeAction($action);
    }

    /**
     * Shows the group list.
     *
     * @return Response
     */
    public function actionIndex(): Response
    {
        $variables = [];
        $variables['groups'] = Plugin::getInstance()->groups->getAllGroups();

        return $this->renderTemplate('vredirect/groups/_index', $variables);
    }

    /**
     * Edit a group.
     *
     * @param int|null $id The group’s id, if editing an existing group.
     * @param Group|null $group The group being edited, if there were any validation errors.
     * @return Response
     * @throws ForbiddenHttpException if the user is not an admin
     * @throws NotFoundHttpException if the requested group` cannot be found
     */
    public function actionEdit(?int $id = null, ?Group $group = null): Response
    {
        $this->requireAdmin();

        $groupsService = Plugin::getInstance()->groups;

        if ($group === null) {
            if ($id !== null) {
                $group = $groupsService->getGroupById($id);

                if ($group === null) {
                    throw new NotFoundHttpException('Group not found');
                }
            }
        }

        if ($id && $groupsService->getGroupById($id)) {
            $title = trim($group->name ?: Plugin::t('Edit Redirect Group'));
        } else {
            $title = Plugin::t('Create new redirect group');
        }
        if (!$group) {
            $group = new Group();
        }

        return $this->asCpScreen()
            ->title($title)
            ->selectedSubnavItem('groups')
            ->addCrumb(Plugin::t('Redirects'), 'redirect')
            ->addCrumb(Plugin::t('Groups'), 'redirect/groups')
            ->action('vredirect/groups/save')
            ->redirectUrl('redirect/groups')
            ->contentTemplate('vredirect/groups/_edit', [
                'group' => $group,
            ]);
    }

    /**
     * Saves a group.
     *
     * @return Response|null
     * @throws BadRequestHttpException
     */
    public function actionSave(): ?YiiResponse
    {
        $this->requirePostRequest();

        $groupsService = Plugin::getInstance()->groups;

        $groupId = $this->request->getBodyParam('id');
        $groupName = $this->request->getBodyParam('name');
        $groupDescription = $this->request->getBodyParam('description');

        if ($groupId) {
            $group = $groupsService->getGroupById($groupId);
            if (!$group) {
                throw new BadRequestHttpException("Invalid category group ID: $groupId");
            }
        } else {
            $group = new Group();
        }
        $group->name = $groupName;
        $group->description = $groupDescription;

        if (!$groupsService->saveGroup($group)) {
            return $this->asModelFailure($group, Plugin::t('Couldn’t save redirect group.'), 'group');
        }

        return $this->asModelSuccess($group, Plugin::t('Group saved.'), 'group');
    }

    /**
     * Removes a group.
     */
    public function actionDelete(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();
        $id = $this->request->getRequiredBodyParam('id');
        $groupsService = Plugin::getInstance()->groups;
        $group = $groupsService->getGroupById($id);

        if ($group) {
            $groupsService->deleteGroupById($group->id);
        }

        return $this->asSuccess();
    }
}
