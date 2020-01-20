<?php
/**
 * Upvote plugin for Craft CMS
 *
 * Lets your users upvote/downvote, "like", or favorite any type of element.
 *
 * @author    Double Secret Agency
 * @link      https://www.doublesecretagency.com/
 * @copyright Copyright (c) 2014 Double Secret Agency
 */

namespace doublesecretagency\upvote\controllers;

use Craft;
use craft\web\Controller;
use doublesecretagency\upvote\Upvote;
use yii\web\Response;

/**
 * Class PageController
 * @since 2.1.0
 */
class PageController extends Controller
{

    // Protected Properties
    // =========================================================================

    /**
     * @var    bool|array Allows anonymous access to this controller's actions.
     * @access protected
     */
    protected $allowAnonymous = true;

    /**
     * Check the preload config setting.
     *
     * @return Response
     */
    public function actionPreload(): Response
    {
        // Get preload config setting
        $preload = (bool) Upvote::$plugin->getSettings()->preload;

        // Return whether or not preloading is enabled
        return $this->asJson(['enabled' => $preload]);
    }

    /**
     * Generate a valid CSRF token & name.
     *
     * @return Response
     */
    public function actionCsrf(): Response
    {
        // Get request service
        $request = Craft::$app->getRequest();

        // Return CSRF token as JSON
        return $this->asJson([
            $request->csrfParam => $request->getCsrfToken()
        ]);
    }

    /**
     * Configure all DOM elements generated by Upvote.
     *
     * @return Response
     */
    public function actionConfigure() // : Response
    {
        // Initialize list of elements to compile
        $data = [];

        // Get POST values
        $ids = Craft::$app->getRequest()->getBodyParam('ids[]');

        // If IDs are not an array, or are empty, bail
        if (!is_array($ids) || empty($ids)) {
            return;
        }

        // Loop through IDs provided
        foreach ($ids as $itemKey) {
            // Compile individual element
            $data[] = $this->_compileElement($itemKey);
        }

        // Return response data
        return $this->asJson($data);
    }

    /**
     */
    private function _compileElement($itemKey)
    {
        // Split ID into array
        $parts = explode(':', $itemKey);

        // Get the element ID
        $elementId = (int) array_shift($parts);

        // If no element ID, bail
        if (!$elementId) {
            return;
        }

        // Reassemble the remaining parts (in case the key contains a colon)
        $key = implode(':', $parts);

        // If no key, set to null
        if (!$key) {
            $key = null;
        }

        // Return element's vote data
        return [
            'id' => $elementId,
            'key' => $key,
            'itemKey' => $itemKey,
            'userVote' => $this->_userVote($itemKey),
            'tally' => Upvote::$plugin->upvote_query->tally($elementId, $key),
        ];
    }

    /**
     * Get the user's vote for specified element.
     */
    private function _userVote($itemKey)
    {
        // If login is required
        if (Upvote::$plugin->getSettings()->requireLogin) {
            // Get user history from DB
            $history = Upvote::$plugin->upvote->loggedInHistory;
        } else {
            // Get anonymous user history
            $history = Upvote::$plugin->upvote->anonymousHistory;
        }

        // Return the user's vote for specified element
        return ($history[$itemKey] ?? null);
    }

}
