<?php

/**
 * Functional tests for the SDK
 *
 * PHP version 5
 *
 * LICENSE: Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @category   Microsoft
 * @package    Tests\Functional\WindowsAzure\Services\Queue
 * @author     Jason Cooke <jcooke@microsoft.com>
 * @copyright  2012 Microsoft Corporation
 * @license    http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 * @link       http://pear.php.net/package/azure-sdk-for-php
 */

namespace Tests\Functional\WindowsAzure\Services\Queue;

use \HTTP_Request2_LogicException;
use WindowsAzure\Core\ServiceException;
use WindowsAzure\Core\WindowsAzureUtilities;
use WindowsAzure\Services\Core\Models\Logging;
use WindowsAzure\Services\Core\Models\Metrics;
use WindowsAzure\Services\Core\Models\RetentionPolicy;
use WindowsAzure\Services\Core\Models\ServiceProperties;
use WindowsAzure\Services\Queue\Models\CreateMessageOptions;
use WindowsAzure\Services\Queue\Models\CreateQueueOptions;
use WindowsAzure\Services\Queue\Models\ListMessagesOptions;
use WindowsAzure\Services\Queue\Models\ListQueuesOptions;
use WindowsAzure\Services\Queue\Models\PeekMessagesOptions;
use WindowsAzure\Services\Queue\Models\QueueServiceOptions;

class QueueServiceFunctionalTest extends FunctionalTestBase {
    // ----------------------------
    // --- getServiceProperties ---
    // ----------------------------

    /**
    * @covers WindowsAzure\Services\Queue\QueueRestProxy::getServiceProperties
    * @covers WindowsAzure\Services\Queue\QueueRestProxy::setServiceProperties
    */
    public function testGetServicePropertiesNoOptions() {
        $serviceProperties = QueueServiceFunctionalTestData::getDefaultServiceProperties();
       
        $shouldReturn = false;
        try {
            $this->wrapper->setServiceProperties($serviceProperties);
            $this->assertFalse(WindowsAzureUtilities::isEmulated(), 'Should succeed when not running in emulator');
        } catch (ServiceException $e) {
            // Expect failure in emulator, as v1.6 doesn't support this method
            if (WindowsAzureUtilities::isEmulated()) {
                $this->assertEquals(400, $e->getCode(), 'getCode');
                $shouldReturn = true;
            } else {
                throw $e;
            }
        }
        if($shouldReturn) {
            return;
        }

        $this->getServicePropertiesWorker(null);
    }

    /**
    * @covers WindowsAzure\Services\Queue\QueueRestProxy::getServiceProperties
    * @covers WindowsAzure\Services\Queue\QueueRestProxy::setServiceProperties
    */
    public function testGetServiceProperties() {
        $serviceProperties = QueueServiceFunctionalTestData::getDefaultServiceProperties();

        $shouldReturn = false;
        try {
            $this->wrapper->setServiceProperties($serviceProperties);
            $this->assertFalse(WindowsAzureUtilities::isEmulated(), 'Should succeed when not running in emulator');
        } catch (ServiceException $e) {
            // Expect failure in emulator, as v1.6 doesn't support this method
            if (WindowsAzureUtilities::isEmulated()) {
                $this->assertEquals(400, $e->getCode(), 'getCode');
                $shouldReturn = true;
            } else {
                throw $e;
            }
        }
        if($shouldReturn) {
            return;
        }

        // Now look at the combos.
        $interestingTimeouts = QueueServiceFunctionalTestData::getInterestingTimeoutValues();
        foreach($interestingTimeouts as $timeout)  {
            $options = new QueueServiceOptions();
            // TODO: Revert when fixed
            // https://github.com/WindowsAzure/azure-sdk-for-php/issues/69
            // $options->setTimeout($timeout);
            $options->setTimeout($timeout . '');
            $this->getServicePropertiesWorker($options);
        }
    }

    private function getServicePropertiesWorker($options) {
        self::println( 'Trying $options: ' . self::tmptostring($options));
        $effOptions = ($options == null ? new QueueServiceOptions() : $options);
        try {
            $ret = ($options == null ? $this->wrapper->getServiceProperties() : $this->wrapper->getServiceProperties($effOptions));

            // TODO: Revert when fixed
            // https://github.com/WindowsAzure/azure-sdk-for-php/issues/101
            // if ($effOptions->getTimeout() != null && $effOptions->getTimeout() < 1) {
            if ($effOptions->getTimeout() != null && $effOptions->getTimeout() < 0) {
                $this->True('Expect negative timeouts in $options to throw', false);
            } else {
                $this->assertFalse(WindowsAzureUtilities::isEmulated(), 'Should succeed when not running in emulator');
            }
            $this->verifyServicePropertiesWorker($ret, null);
        }
        catch (ServiceException $e) {
            if (WindowsAzureUtilities::isEmulated()) {
                if ($options->getTimeout() != null && $options->getTimeout() < 0) {
                    $this->assertEquals(500, $e->getCode(), 'getCode');
                } else {
                // Expect failure in emulator, as v1.6 doesn't support this method
                    $this->assertEquals(400, $e->getCode(), 'getCode');
                }
            } else {
                if ($effOptions->getTimeout() == null || $effOptions->getTimeout() >= 1) {
                    $this->assertNull($e, 'Expect positive timeouts in $options to be fine');
                }
                else {
                    $this->assertEquals(500, $e->getCode(), 'getCode');
                }
            }
        }
    }

    private function verifyServicePropertiesWorker($ret, $serviceProperties) {
        if ($serviceProperties == null) {
            $serviceProperties = QueueServiceFunctionalTestData::getDefaultServiceProperties();
        }

        $sp = $ret->getValue();
        $this->assertNotNull($sp, 'getValue should be non-null');

        $l = $sp->getLogging();
        $this->assertNotNull($l, 'getValue()->getLogging() should be non-null');
        $this->assertEquals($serviceProperties->getLogging()->getVersion(), $l->getVersion(), 'getValue()->getLogging()->getVersion');
        $this->assertEquals($serviceProperties->getLogging()->getDelete(), $l->getDelete(), 'getValue()->getLogging()->getDelete');
        $this->assertEquals($serviceProperties->getLogging()->getRead(), $l->getRead(), 'getValue()->getLogging()->getRead');
        $this->assertEquals($serviceProperties->getLogging()->getWrite(), $l->getWrite(), 'getValue()->getLogging()->getWrite');

        $r = $l->getRetentionPolicy();
        $this->assertNotNull($r, 'getValue()->getLogging()->getRetentionPolicy should be non-null');
        $this->assertEquals($serviceProperties->getLogging()->getRetentionPolicy()->getDays(), $r->getDays(), 'getValue()->getLogging()->getRetentionPolicy()->getDays');

        $m = $sp->getMetrics();
        $this->assertNotNull($m, 'getValue()->getMetrics() should be non-null');
        $this->assertEquals($serviceProperties->getMetrics()->getVersion(), $m->getVersion(), 'getValue()->getMetrics()->getVersion');
        $this->assertEquals($serviceProperties->getMetrics()->getEnabled(), $m->getEnabled(), 'getValue()->getMetrics()->getEnabled');
        $this->assertEquals($serviceProperties->getMetrics()->getIncludeAPIs(), $m->getIncludeAPIs(), 'getValue()->getMetrics()->getIncludeAPIs');

        $r = $m->getRetentionPolicy();
        $this->assertNotNull($r, 'getValue()->getMetrics()->getRetentionPolicy should be non-null');
        $this->assertEquals($serviceProperties->getMetrics()->getRetentionPolicy()->getDays(), $r->getDays(), 'getValue()->getMetrics()->getRetentionPolicy()->getDays');
    }

    // ----------------------------
    // --- setServiceProperties ---
    // ----------------------------

    /**
    * @covers WindowsAzure\Services\Queue\QueueRestProxy::getServiceProperties
    * @covers WindowsAzure\Services\Queue\QueueRestProxy::setServiceProperties
    */
    public function testSetServicePropertiesNoOptions() {
        $serviceProperties = QueueServiceFunctionalTestData::getDefaultServiceProperties();
        $this->setServicePropertiesWorker($serviceProperties, null);
    }

    /**
    * @covers WindowsAzure\Services\Queue\QueueRestProxy::getServiceProperties
    * @covers WindowsAzure\Services\Queue\QueueRestProxy::setServiceProperties
    */
    public function testSetServiceProperties() {
        $interestingServiceProperties = QueueServiceFunctionalTestData::getInterestingServiceProperties();
        foreach($interestingServiceProperties as $serviceProperties)  {
            $interestingTimeouts = QueueServiceFunctionalTestData::getInterestingTimeoutValues();
            foreach($interestingTimeouts as $timeout)  {
                $options = new QueueServiceOptions();
                // TODO: Revert when fixed
                // https://github.com/WindowsAzure/azure-sdk-for-php/issues/69
                // $options->setTimeout($timeout);
                $options->setTimeout($timeout . '');
                $this->setServicePropertiesWorker($serviceProperties, $options);
            }
        }
        $this->wrapper->setServiceProperties($interestingServiceProperties[0]);
    }

    private function setServicePropertiesWorker($serviceProperties, $options) {
        self::println( 'Trying $options: ' . self::tmptostring($options) . 
                ' and $serviceProperties' . self::tmptostring($serviceProperties));
        
        try {
            if ($options == null) {
                $this->wrapper->setServiceProperties($serviceProperties);
            } else {
                $this->wrapper->setServiceProperties($serviceProperties, $options);
            }

            if ($options == null) {
                $options = new QueueServiceOptions();
            }

            // TODO: Revert when fixed
            // https://github.com/WindowsAzure/azure-sdk-for-php/issues/101
            // if ($options->getTimeout() != null && $options->getTimeout() < 1) {
            if ($options->getTimeout() != null && $options->getTimeout() < 0) {
                $this->assertTrue(false, 'Expect negative timeouts in $options to throw');
            } else {
                $this->assertFalse(WindowsAzureUtilities::isEmulated(), 'Should succeed when not running in emulator');
            }

            $ret = ($options == null ? $this->wrapper->getServiceProperties() : $this->wrapper->getServiceProperties($options));

            $this->verifyServicePropertiesWorker($ret, $serviceProperties);

        } catch (ServiceException $e) {
            if ($options == null) {
                $options = new QueueServiceOptions();
            }

            if (WindowsAzureUtilities::isEmulated()) {
                if ($options->getTimeout() != null && $options->getTimeout() < 0) {
                    $this->assertEquals(500, $e->getCode(), 'getCode');
                } else {
                    $this->assertEquals(400, $e->getCode(), 'getCode');
                }
            } else {
                if ($options->getTimeout() != null && $options->getTimeout() < 1) {
                    $this->assertEquals(500, $e->getCode(), 'getCode');
                } else {
                    $this->assertNull($e, 'Expect positive timeouts in $options to be fine');
                }
            }
        }
    }

    // ------------------
    // --- listQueues ---
    // ------------------

    /**
    * @covers WindowsAzure\Services\Queue\QueueRestProxy::listQueues
    */
    public function testListQueuesNoOptions() {
        $this->listQueuesWorker(null);
    }

    /**
    * @covers WindowsAzure\Services\Queue\QueueRestProxy::listQueues
    */
    public function testListQueues() {
        $interestingListQueuesOptions = QueueServiceFunctionalTestData::getInterestingListQueuesOptions();
        foreach($interestingListQueuesOptions as $options)  {
            $this->listQueuesWorker($options);
        }
    }

    private function listQueuesWorker($options) {
        self::println( 'Trying $options: ' . self::tmptostring($options));
        $finished = false;
        while (!$finished) {
            try {
                $ret = ($options == null ? $this->wrapper->listQueues() : $this->wrapper->listQueues($options));

                if ($options == null) {
                    $options = new ListQueuesOptions();
                }

                // TODO: Revert when fixed
                // https://github.com/WindowsAzure/azure-sdk-for-php/issues/101
//                if ($options->getTimeout() != null && $options->getTimeout() < 1) {

                // TODO: Uncomment when fixed
                // https://github.com/WindowsAzure/azure-sdk-for-php/issues/103
//                if ($options->getTimeout() != null && $options->getTimeout() < 0) {
//                    $this->assertTrue(false, 'Expect negative timeouts ' . $options->getTimeout() . ' in $options to throw');
//                }
                $this->verifyListQueuesWorker($ret, $options);

                if (strlen($ret->getNextMarker()) == 0) {
                    self::println('Done with this loop');
                    $finished = true;
                }
                else {
                    self::println('Cycling to get the next marker: ' . $ret->getNextMarker());
                    $options->setMarker($ret->getNextMarker());
                }
            }
            catch (ServiceException $e) {
                $finished = true;
                if ($options->getTimeout() == null || $options->getTimeout() >= 1) {
                    $this->assertNull($e, 'Expect positive timeouts in $options to be fine');
                }
                else {
                    $this->assertEquals(500, $e->getCode(), 'getCode');
                }
            }
        }
    }

    private function verifyListQueuesWorker($ret, $options) {
        // Uncomment when fixed
        // https://github.com/WindowsAzure/azure-sdk-for-php/issues/98
        //$this->assertEquals($accountName, $ret->getAccountName(), 'getAccountName');
        
        // Cannot really check the next marker. Just make sure it is not null.
        $this->assertNotNull($ret->getNextMarker(), 'getNextMarker');
        $this->assertEquals($options->getMarker(), $ret->getMarker(), 'getMarker');
        $this->assertEquals($options->getMaxResults(), $ret->getMaxResults(), 'getMaxResults');
        $this->assertEquals($options->getPrefix(), $ret->getPrefix(), 'getPrefix');

        $this->assertNotNull($ret->getQueues(), 'getQueues');
         
        if ($options->getMaxResults() == 0) {
            $this->assertNull($ret->getNextMarker(), 'When MaxResults is 0, expect getNextMarker (' . $ret->getNextMarker() . ')to be null');

            if ($options->getPrefix() != null && $options->getPrefix() == QueueServiceFunctionalTestData::$nonExistQueuePrefix) {
                $this->assertEquals(0, count($ret->getQueues()), 'when MaxResults=0 and Prefix=(\'' . $options->getPrefix() . '\'), then Queues->length');
            }
            else if ($options->getPrefix() != null && $options->getPrefix() == QueueServiceFunctionalTestData::$testUniqueId) {
                $this->assertEquals(count(QueueServiceFunctionalTestData::$TEST_QUEUE_NAMES), count($ret->getQueues()), 'when MaxResults=0 and Prefix=(\'' . $options->getPrefix() . '\'), then count Queues');
            }
            else {
                // Don't know how many there should be
            }
        }
        else if (strlen($ret->getNextMarker()) == 0) {
            $this->assertTrue(count($ret ->getQueues()) <= $options->getMaxResults(), 'when NextMarker (\'' . $ret->getNextMarker() . '\')==\'\', Queues->length (' . count($ret->getQueues()) . ') should be <= MaxResults (' . $options->getMaxResults() . ')');

            if ($options->getPrefix() != null && $options->getPrefix() == QueueServiceFunctionalTestData::$nonExistQueuePrefix) {
                $this->assertEquals(0, count($ret->getQueues()), 'when no next marker and Prefix=(\'' . $options->getPrefix() . '\'), then Queues->length');
            }
            else if ($options->getPrefix() != null && $options->getPrefix() == QueueServiceFunctionalTestData::$testUniqueId) {
                // Need to futz with the mod because you are allowed to get MaxResults items returned->
                $this->assertEquals(count(QueueServiceFunctionalTestData::$TEST_QUEUE_NAMES) % $options->getMaxResults(), count($ret ->getQueues()) % $options->getMaxResults(), 'when no next marker and Prefix=(\'' . $options->getPrefix() . '\'), then Queues->length');
            }
            else {
                // Don't know how many there should be
            }
        }
        else {
            $this->assertEquals(count($ret ->getQueues()), $options->getMaxResults(),
                    'when NextMarker (' . $ret->getNextMarker() .
                    ')!=\'\', Queues->length (' . count($ret->getQueues()) . 
                    ') should be == MaxResults (' . $options->getMaxResults() . ')');

            if ($options->getPrefix() != null && $options->getPrefix() == (QueueServiceFunctionalTestData::$nonExistQueuePrefix)) {
                $this->assertTrue(false, 'when a next marker and Prefix=(\'' . $options->getPrefix() . '\'), impossible');
            }
        }

        // TODO: Need to verify the queue content?
    }

    // -------------------
    // --- createQueue --- 
    // -------------------  

    /**
    * @covers WindowsAzure\Services\Queue\QueueRestProxy::createQueue
    * @covers WindowsAzure\Services\Queue\QueueRestProxy::deleteQueue
    * @covers WindowsAzure\Services\Queue\QueueRestProxy::getQueueMetadata
    * @covers WindowsAzure\Services\Queue\QueueRestProxy::listQueues
    */
    public function testCreateQueueNoOptions() {
        $this->createQueueWorker(null);
    }

    /**
    * @covers WindowsAzure\Services\Queue\QueueRestProxy::createQueue
    * @covers WindowsAzure\Services\Queue\QueueRestProxy::deleteQueue
    * @covers WindowsAzure\Services\Queue\QueueRestProxy::getQueueMetadata
    * @covers WindowsAzure\Services\Queue\QueueRestProxy::listQueues
    */
    public function testCreateQueue() {
        $interestingCreateQueueOptions = QueueServiceFunctionalTestData::getInterestingCreateQueueOptions();
        foreach($interestingCreateQueueOptions as $options)  {
            $this->createQueueWorker($options);
        }
    }

    private function createQueueWorker($options) {
        self::println( 'Trying $options: ' . self::tmptostring($options));
        $queue = QueueServiceFunctionalTestData::getInterestingQueueName();
        $created = false;

        try {
            if ($options == null) {
                $this->wrapper->createQueue($queue);
            }
            else {
                $this->wrapper->createQueue($queue, $options);
            }
            $created = true;

            if ($options == null) {
                $options = new CreateQueueOptions();
            }

            // Uncomment when fixed
            // https://github.com/WindowsAzure/azure-sdk-for-php/issues/59
//            if ($options->getTimeout() != null && $options->getTimeout() < 1) {
//                $this->assertTrue(false, 'Expect negative timeouts in $options to throw');
//            }

            // Now check that the queue was created correctly.

            // Make sure that the list of all applicable queues is correctly updated.
            $opts = new ListQueuesOptions();
            $opts->setPrefix(QueueServiceFunctionalTestData::$testUniqueId);
            $qs = $this->wrapper->listQueues($opts);
            $this->assertEquals(count($qs->getQueues()), (count(QueueServiceFunctionalTestData::$TEST_QUEUE_NAMES) + 1), 'After adding one, with Prefix=(\'' . QueueServiceFunctionalTestData::$testUniqueId . '\'), then Queues->length');

            // Check the metadata on the queue
            $ret = $this->wrapper->getQueueMetadata($queue);
            $this->verifyCreateQueueWorker($ret, $options);
            $this->wrapper->deleteQueue($queue);
            $created = false;
        }
        catch (ServiceException $e) {
            if ($options == null) {
                $options = new CreateQueueOptions();
            }
            // Uncomment when fixed
            // https://github.com/WindowsAzure/azure-sdk-for-php/issues/59
//            if ($options->getTimeout() == null || $options->getTimeout() >= 1) {
//                $this->assertNull($e, 'Expect positive timeouts in $options to be fine');
//            }
//            else 
                {
                $this->assertEquals(500, $e->getCode(), 'getCode');
            }
        }
        if ($created) {
            $this->wrapper->deleteQueue($queue);
        }
    }

    private function verifyCreateQueueWorker($ret, $options) {
        self::println( 'Trying $options: ' . self::tmptostring($options) . 
                ' and ret ' . self::tmptostring($ret));
        if ($options == null) {
            $options = QueueServiceFunctionalTestData::getInterestingCreateQueueOptions();
            $options = $options[0];
        }

        if ($options->getMetadata() == null) {
            $this->assertNotNull($ret->getMetadata(), 'queue Metadata');
            $this->assertEquals(0, count($ret->getMetadata()), 'queue Metadata count');
        }
        else {
            $this->assertNotNull($ret->getMetadata(), 'queue Metadata');
            $this->assertEquals(count($options->getMetadata()), count($ret->getMetadata()), 'Metadata');
            $om = $options->getMetadata();
            $rm = $ret->getMetadata();
            foreach(array_keys($options->getMetadata()) as $key)  {
                $this->assertEquals($om[$key], $rm[$key], 'Metadata(' . $key . ')');
            }
        }
        // TODO: Need to verify the queue content?
    }

    // -------------------
    // --- deleteQueue ---
    // -------------------

    /**
    * @covers WindowsAzure\Services\Queue\QueueRestProxy::createQueue
    * @covers WindowsAzure\Services\Queue\QueueRestProxy::deleteQueue
    * @covers WindowsAzure\Services\Queue\QueueRestProxy::listQueues
    */
    public function testDeleteQueueNoOptions() {
        $this->deleteQueueWorker(null);
    }

    /**
    * @covers WindowsAzure\Services\Queue\QueueRestProxy::createQueue
    * @covers WindowsAzure\Services\Queue\QueueRestProxy::deleteQueue
    * @covers WindowsAzure\Services\Queue\QueueRestProxy::listQueues
    */
    public function testDeleteQueue() {
        $interestingTimeouts = QueueServiceFunctionalTestData::getInterestingTimeoutValues();
        foreach($interestingTimeouts as $timeout)  {
            $options = new QueueServiceOptions();
            $options->setTimeout($timeout);
            $this->deleteQueueWorker($options);
        }
    }

    private function deleteQueueWorker($options) {
        self::println( 'Trying $options: ' . self::tmptostring($options));
        $queue = QueueServiceFunctionalTestData::getInterestingQueueName();

        // Make sure there is something to delete.
        $this->wrapper->createQueue($queue);

        // Make sure that the list of all applicable queues is correctly updated.
        $opts = new ListQueuesOptions();
        $opts->setPrefix(QueueServiceFunctionalTestData::$testUniqueId);
        $qs = $this->wrapper->listQueues($opts);
        $this->assertEquals(count($qs->getQueues()), (count(QueueServiceFunctionalTestData::$TEST_QUEUE_NAMES) + 1), 'After adding one, with Prefix=(\'' . QueueServiceFunctionalTestData::$testUniqueId . '\'), then Queues->length');

        $deleted = false;
        try {
            if ($options == null) {
                $this->wrapper->deleteQueue($queue);
            }
            else {
                $this->wrapper->deleteQueue($queue, $options);
            }

            $deleted = true;

            if ($options == null) {
                $options = new QueueServiceOptions();
            }

            // Uncomment when fixed
            // https://github.com/WindowsAzure/azure-sdk-for-php/issues/59
//            if ($options->getTimeout() != null && $options->getTimeout() < 1) {
//                $this->assertTrue(false, 'Expect negative timeouts in $options to throw');
//            }

            // Make sure that the list of all applicable queues is correctly updated.
            $opts = new ListQueuesOptions();
            $opts->setPrefix(QueueServiceFunctionalTestData::$testUniqueId);
            $qs = $this->wrapper->listQueues($opts);
            $this->assertEquals(count($qs->getQueues()), count(QueueServiceFunctionalTestData::$TEST_QUEUE_NAMES), 'After adding then deleting one, with Prefix=(\'' . QueueServiceFunctionalTestData::$testUniqueId . '\'), then Queues->length');

            // Nothing else interesting to check for the options.
        }
        catch (ServiceException $e) {
            // Uncomment when fixed
            // https://github.com/WindowsAzure/azure-sdk-for-php/issues/59
//            if ($options->getTimeout() == null || $options->getTimeout() >= 1) {
//                $this->assertNull($e, 'Expect positive timeouts in $options to be fine');
//            }
//            else
            {
                $this->assertEquals(500, $e->getCode(), 'getCode');
            }
        }
        if (!$deleted) {
            echo ('Test didn\'t delete the $queue, so try again more simply');
                // Try again. If it doesn't work, not much else to try.
            $this->wrapper->deleteQueue($queue);
        }
    }

    // TODO: Negative tests, like accessing a non-existant queue, or recreating an existing queue?

    // ------------------------------------------
    // --- getQueueMetadata, setQueueMetadata ---
    // ------------------------------------------

    /**
    * @covers WindowsAzure\Services\Queue\QueueRestProxy::createMessage
    * @covers WindowsAzure\Services\Queue\QueueRestProxy::createQueue
    * @covers WindowsAzure\Services\Queue\QueueRestProxy::deleteQueue
    * @covers WindowsAzure\Services\Queue\QueueRestProxy::getQueueMetadata
    * @covers WindowsAzure\Services\Queue\QueueRestProxy::setQueueMetadata
    */
    public function testGetQueueMetadataNoOptions() {
        $interestingMetadata = QueueServiceFunctionalTestData::getNiceMetadata();
        foreach ($interestingMetadata as $metadata) {
            $this->getQueueMetadataWorker(null, $metadata);
        }
    }

    /**
    * @covers WindowsAzure\Services\Queue\QueueRestProxy::createMessage
    * @covers WindowsAzure\Services\Queue\QueueRestProxy::createQueue
    * @covers WindowsAzure\Services\Queue\QueueRestProxy::deleteQueue
    * @covers WindowsAzure\Services\Queue\QueueRestProxy::getQueueMetadata
    * @covers WindowsAzure\Services\Queue\QueueRestProxy::setQueueMetadata
    */
    public function testGetQueueMetadata() {
        $interestingTimeouts = QueueServiceFunctionalTestData::getInterestingTimeoutValues();
        $interestingMetadata = QueueServiceFunctionalTestData::getNiceMetadata();

        foreach($interestingTimeouts as $timeout)  {
            foreach ($interestingMetadata as $metadata) {
                $options = new QueueServiceOptions();
                $options->setTimeout($timeout);
                $this->getQueueMetadataWorker($options, $metadata);
            }
        }
    }

    private function getQueueMetadataWorker($options, $metadata) {
        self::println( 'Trying $options: ' . self::tmptostring($options) . 
                ' and $metadata: ' . self::tmptostring($metadata));
        $queue = QueueServiceFunctionalTestData::getInterestingQueueName();

        // Make sure there is something to test
        $this->wrapper->createQueue($queue);

        // Put some messages to verify getApproximateMessageCount 
        if ($metadata != null) {
            for ($i = 0; $i < count($metadata); $i++) {
                $this->wrapper->createMessage($queue, 'message ' . $i);
            }

            // And put in some metadata
            $this->wrapper->setQueueMetadata($queue, $metadata);
        }

        try {
            $res = ($options == null ? $this->wrapper->getQueueMetadata($queue) : $this->wrapper->getQueueMetadata( $queue, $options));

            if ($options == null) {
                $options = new QueueServiceOptions();
            }

            // Uncomment when fixed
            // https://github.com/WindowsAzure/azure-sdk-for-php/issues/59
//            if ($options->getTimeout() != null && $options->getTimeout() < 1) {
//                $this->assertTrue(false, 'Expect negative timeouts in $options to throw');
//            }

            $this->verifyGetSetQueueMetadataWorker($res, $metadata);
        }
        catch (ServiceException $e) {
            // Uncomment when fixed
            // https://github.com/WindowsAzure/azure-sdk-for-php/issues/59
//            if ($options->getTimeout() == null || $options->getTimeout() >= 1) {
//                $this->assertNull($e, 'Expect positive timeouts in $options to be fine');
//            }
//            else
            {
                $this->assertEquals(500, $e->getCode(), 'getCode');
            }
        }
        // Clean up->
        $this->wrapper->deleteQueue($queue);
    }

    private function verifyGetSetQueueMetadataWorker($ret, $metadata) {
        $this->assertNotNull($ret->getMetadata(), 'queue Metadata');
        if ($metadata == null) {
            $this->assertEquals(0, count($ret->getMetadata()), 'Metadata');
            $this->assertEquals(0, $ret->getApproximateMessageCount(), 'getApproximateMessageCount');
        }
        else {
            $this->assertEquals(count($metadata), count($ret->getMetadata()), 'Metadata');
            $rm =$ret->getMetadata();
            foreach(array_keys($metadata) as $key)  {
                $this->assertEquals($metadata[$key], $rm[$key], 'Metadata(' . $key . ')');
            }

            // Hard to test "approximate", so just verify that it is in the expected range
            $this->assertTrue(
                    (0 <= $ret->getApproximateMessageCount()) && ($ret->getApproximateMessageCount() <= count($metadata)),
                    '0 <= getApproximateMessageCount (' . $ret->getApproximateMessageCount() . ') <= $metadata count (' . count($metadata) . ')');
        }
    }

    // ------------------------
    // --- setQueueMetadata ---
    // ------------------------

    /**
    * @covers WindowsAzure\Services\Queue\QueueRestProxy::createQueue
    * @covers WindowsAzure\Services\Queue\QueueRestProxy::deleteQueue
    * @covers WindowsAzure\Services\Queue\QueueRestProxy::getQueueMetadata
    * @covers WindowsAzure\Services\Queue\QueueRestProxy::setQueueMetadata
    */
    public function testSetQueueMetadataNoOptions() {
        $interestingMetadata = QueueServiceFunctionalTestData::getInterestingMetadata();
        foreach ($interestingMetadata as $metadata) {
            if ($metadata == null) {
                // This is tested above.
                continue;
            }
            $this->setQueueMetadataWorker(null, $metadata);
        }
    }

    /**
    * @covers WindowsAzure\Services\Queue\QueueRestProxy::createQueue
    * @covers WindowsAzure\Services\Queue\QueueRestProxy::deleteQueue
    * @covers WindowsAzure\Services\Queue\QueueRestProxy::getQueueMetadata
    * @covers WindowsAzure\Services\Queue\QueueRestProxy::setQueueMetadata
    */
    public function testSetQueueMetadata() {
        $interestingTimeouts = QueueServiceFunctionalTestData::getInterestingTimeoutValues();
        $interestingMetadata = QueueServiceFunctionalTestData::getInterestingMetadata();

        foreach($interestingTimeouts as $timeout)  {
            foreach ($interestingMetadata as $metadata) {
                if ($metadata == null) {
                    // This is tested above.
                    continue;
                }
                $options = new QueueServiceOptions();
                $options->setTimeout($timeout);
                $this->setQueueMetadataWorker($options, $metadata);
            }
        }
    }

    private function setQueueMetadataWorker($options, $metadata) {
        self::println( 'Trying $options: ' . self::tmptostring($options) . 
                ' and $metadata: ' . self::tmptostring($metadata));
        $queue = QueueServiceFunctionalTestData::getInterestingQueueName();

        // Make sure there is something to test
        $this->wrapper->createQueue($queue);

        try {
            // And put in some metadata
            if ($options == null) {
                $this->wrapper->setQueueMetadata($queue, $metadata);
            }
            else {
                $this->wrapper->setQueueMetadata($queue, $metadata, $options);
            }

            if ($options == null) {
                $options = new QueueServiceOptions();
            }

            // Uncomment when fixed
            // https://github.com/WindowsAzure/azure-sdk-for-php/issues/59
//            if ($options->getTimeout() != null && $options->getTimeout() < 1) {
//                $this->assertTrue(false, 'Expect negative timeouts in $options to throw');
//            }

            $res = $this->wrapper->getQueueMetadata($queue);
            $this->verifyGetSetQueueMetadataWorker($res, $metadata);
        }
        catch (HTTP_Request2_LogicException $le) {
            $keypart = array_keys($metadata);
            $keypart = $keypart[0];
            if ($metadata != null && count($metadata) > 0 && (substr($keypart, 0, 1) == '<')) {
                // Trying to pass bad metadata
            }            
            else {
                throw $le;
            }
        }
//        catch (ServiceException $e) {
//            // Uncomment when fixed
//            // https://github.com/WindowsAzure/azure-sdk-for-php/issues/59
////            else if ($options->getTimeout() != null && $options->getTimeout() < 1) {
////                $this->assertEquals(500, $e->getCode(), 'getCode');
////            }
//            else {
//                $this->assertNull($e, 'Expect positive timeouts in $options to be fine');
//            }
//        }
        // Clean up.
        $this->wrapper->deleteQueue($queue);
    }

    // ---------------------
    // --- createMessage ---
    // ---------------------

    /**
    * @covers WindowsAzure\Services\Queue\QueueRestProxy::clearMessages
    * @covers WindowsAzure\Services\Queue\QueueRestProxy::createMessage
    * @covers WindowsAzure\Services\Queue\QueueRestProxy::listMessages
    */
    public function testCreateMessageEmpty() {
        $this->createMessageWorker('', QueueServiceFunctionalTestData::getSimpleCreateMessageOptions());
    }

    /**
    * @covers WindowsAzure\Services\Queue\QueueRestProxy::clearMessages
    * @covers WindowsAzure\Services\Queue\QueueRestProxy::createMessage
    * @covers WindowsAzure\Services\Queue\QueueRestProxy::listMessages
    */
    public function testCreateMessageUnicodeMessage() {
        $this->createMessageWorker('Some unicode: \uB2E4\uB974\uB2E4\uB294\u0625 \u064A\u062F\u064A\u0648', QueueServiceFunctionalTestData::getSimpleCreateMessageOptions());
    }

    /**
    * @covers WindowsAzure\Services\Queue\QueueRestProxy::clearMessages
    * @covers WindowsAzure\Services\Queue\QueueRestProxy::createMessage
    * @covers WindowsAzure\Services\Queue\QueueRestProxy::listMessages
    */
    public function testCreateMessageXmlMessage() {
        $this->createMessageWorker('Some HTML: <this><is></a>', QueueServiceFunctionalTestData::getSimpleCreateMessageOptions());
    }

    /**
    * @covers WindowsAzure\Services\Queue\QueueRestProxy::clearMessages
    * @covers WindowsAzure\Services\Queue\QueueRestProxy::createMessage
    * @covers WindowsAzure\Services\Queue\QueueRestProxy::listMessages
    */
    public function testCreateMessageWithSmallTTL() {
        $queue = QueueServiceFunctionalTestData::$TEST_QUEUE_NAMES;
        $queue = $queue[0];
        $messageText = QueueServiceFunctionalTestData::getSimpleMessageText();

        $options = new CreateMessageOptions();
        // Revert when fixed
        // https://github.com/WindowsAzure/azure-sdk-for-php/issues/69
//        $options->setVisibilityTimeoutInSeconds(2);
//        $options->setTimeToLiveInSeconds(4);
        $options->setVisibilityTimeoutInSeconds('2');
        $options->setTimeToLiveInSeconds('4');

        $this->wrapper->createMessage($queue, $messageText, $options);

        $lmr = $this->wrapper->listMessages($queue);

        // No messages, because it is not visible for 2 seconds.
        $this->assertEquals(0, count($lmr->getQueueMessages()), 'getQueueMessages() count');
        sleep(6);
        // Try again, passed the VisibilityTimeout has passed, but also the 4 second TTL has passed.
        $lmr = $this->wrapper->listMessages($queue);

        $this->assertEquals(0, count($lmr->getQueueMessages()), 'getQueueMessages() count');

        $this->wrapper->clearMessages($queue);
    }

    /**
    * @covers WindowsAzure\Services\Queue\QueueRestProxy::clearMessages
    * @covers WindowsAzure\Services\Queue\QueueRestProxy::createMessage
    * @covers WindowsAzure\Services\Queue\QueueRestProxy::listMessages
    */
    public function testCreateMessage() {
        $interestingTimes = array( null, -1, 0, QueueServiceFunctionalTestData::INTERESTING_TTL, 1000 );
        foreach($interestingTimes as $timeToLiveInSeconds)  {
            foreach($interestingTimes as $visibilityTimeoutInSeconds)  {
                $timeout = null;
                $options = new CreateMessageOptions();
            // Uncomment when fixed
            // https://github.com/WindowsAzure/azure-sdk-for-php/issues/59
//                $options->setTimeout($timeout);

        // Revert when fixed
        // https://github.com/WindowsAzure/azure-sdk-for-php/issues/69
//                $options->setTimeToLiveInSeconds($timeToLiveInSeconds);
//                $options->setVisibilityTimeoutInSeconds($visibilityTimeoutInSeconds);
                $options->setTimeToLiveInSeconds($timeToLiveInSeconds .'');
                $options->setVisibilityTimeoutInSeconds($visibilityTimeoutInSeconds . '');
                $this->createMessageWorker(QueueServiceFunctionalTestData::getSimpleMessageText(), $options);
            }
        }

        foreach($interestingTimes as $timeout)  {
            $timeToLiveInSeconds = 1000;
            $visibilityTimeoutInSeconds = QueueServiceFunctionalTestData::INTERESTING_TTL;
            $options = new CreateMessageOptions();
            // Uncomment when fixed
            // https://github.com/WindowsAzure/azure-sdk-for-php/issues/59
//            $options->setTimeout($timeout);

        // Revert when fixed
        // https://github.com/WindowsAzure/azure-sdk-for-php/issues/69
//            $options->setTimeToLiveInSeconds($timeToLiveInSeconds);
//            $options->setVisibilityTimeoutInSeconds($visibilityTimeoutInSeconds);
            $options->setTimeToLiveInSeconds($timeToLiveInSeconds . '');
            $options->setVisibilityTimeoutInSeconds($visibilityTimeoutInSeconds . '');
            $this->createMessageWorker(QueueServiceFunctionalTestData::getSimpleMessageText(), $options);
        }
    }

    private function createMessageWorker($messageText, $options) {
        self::println( 'Trying $options: ' . self::tmptostring($options));
        $queue = QueueServiceFunctionalTestData::$TEST_QUEUE_NAMES;
        $queue = $queue[0];

        try {
            if ($options == null) {
                $this->wrapper->createMessage($queue, $messageText);
            }
            else {
                $this->wrapper->createMessage($queue, $messageText, $options);
            }

            if ($options == null) {
                $options = new CreateMessageOptions();
            }

            if ($options->getVisibilityTimeoutInSeconds() != null && $options->getVisibilityTimeoutInSeconds() < 0) {
                $this->assertTrue(false, 'Expect negative getVisibilityTimeoutInSeconds in $options to throw');
            }
            // Uncomment when fixed
            // https://github.com/WindowsAzure/azure-sdk-for-php/issues/101
//            else if ($options->getTimeToLiveInSeconds() != null && $options->getTimeToLiveInSeconds() <= 0) {
            else if ($options->getTimeToLiveInSeconds() != null && $options->getTimeToLiveInSeconds() < 0) {
                $this->assertTrue(false, 'Expect negative getVisibilityTimeoutInSeconds in $options to throw');
            }
            // Uncomment when fixed
            // https://github.com/WindowsAzure/azure-sdk-for-php/issues/101
//            else if ($options->getVisibilityTimeoutInSeconds() != null && 
//                    $options->getTimeToLiveInSeconds() != null && 
//                    $options->getVisibilityTimeoutInSeconds() > 0 && 
//                    $options->getTimeToLiveInSeconds() <= $options->getVisibilityTimeoutInSeconds()) {

                // TODO: Uncomment when fixed
                // https://github.com/WindowsAzure/azure-sdk-for-php/issues/103
//            else if ($options->getVisibilityTimeoutInSeconds() != null && 
//                    $options->getTimeToLiveInSeconds() != null && 
//                    $options->getVisibilityTimeoutInSeconds() >= 0 && 
//                    $options->getTimeToLiveInSeconds() <= $options->getVisibilityTimeoutInSeconds()) {
//                $this->assertTrue(false, 'Expect getTimeToLiveInSeconds() <= getVisibilityTimeoutInSeconds in $options to throw');
//            }
            
            // Uncomment when fixed
            // https://github.com/WindowsAzure/azure-sdk-for-php/issues/59
//            else if ($options->getTimeout() != null && $options->getTimeout() < 1) {
//                $this->assertTrue(false, 'Expect negative timeouts in $options to throw');
//            }

            // Check that the message matches
            $lmr = $this->wrapper->listMessages($queue);
            if ($options->getVisibilityTimeoutInSeconds() != null && $options->getVisibilityTimeoutInSeconds() > 0) {
                $this->assertEquals(0, count($lmr->getQueueMessages()), 'getQueueMessages() count');
                sleep(QueueServiceFunctionalTestData::INTERESTING_TTL);
                // Try again, not that the 4 second visibility has passed
                $lmr = $this->wrapper->listMessages($queue);
                if ($options->getVisibilityTimeoutInSeconds() > QueueServiceFunctionalTestData::INTERESTING_TTL) {
                    $this->assertEquals(0, count($lmr->getQueueMessages()), 'getQueueMessages() count');
                }
                else {
                    $this->assertEquals(1, count($lmr->getQueueMessages()), 'getQueueMessages() count');
                    $qm = $lmr->getQueueMessages();
                    $qm = $qm[0];
                    $this->assertEquals($messageText, $qm->getMessageText(), '$qm->getMessageText');
                }
            }
            else {
                $this->assertEquals(1, count($lmr->getQueueMessages()), 'getQueueMessages() count');
                $qm = $lmr->getQueueMessages();
                $qm = $qm[0];
                $this->assertEquals($messageText, $qm->getMessageText(), '$qm->getMessageText');
            }

        }
        catch (ServiceException $e) {
            // Uncomment when fixed
            // https://github.com/WindowsAzure/azure-sdk-for-php/issues/59
//            if ($options->getTimeout() != null && $options->getTimeout() < 1) {
//                $this->assertEquals(500, $e->getCode(), 'getCode');
//            }
//            else
            if ($options->getVisibilityTimeoutInSeconds() != null && $options->getVisibilityTimeoutInSeconds() < 0) {
                // Trying to pass bad metadata
                $this->assertEquals(400, $e->getCode(), 'getCode');
                // TODO: Can check more?
            }
            else if ($options->getTimeToLiveInSeconds() != null && $options->getTimeToLiveInSeconds() <= 0) {
                $this->assertEquals(400, $e->getCode(), 'getCode');
            }
            else if ($options->getVisibilityTimeoutInSeconds() != null && $options->getTimeToLiveInSeconds() != null && $options->getVisibilityTimeoutInSeconds() > 0 && $options->getTimeToLiveInSeconds() <= $options->getVisibilityTimeoutInSeconds()) {
                $this->assertEquals(400, $e->getCode(), 'getCode');
            }
            else {
                $this->assertNull($e, 'Expect positive timeouts in $options to be fine');
            }
            // TODO: More checks to not fall out the end if in error?
        }
        $this->wrapper->clearMessages($queue);
    }

    // ---------------------
    // --- updateMessage ---
    // ---------------------

    /**
    * @covers WindowsAzure\Services\Queue\QueueRestProxy::clearMessages
    * @covers WindowsAzure\Services\Queue\QueueRestProxy::createMessage
    * @covers WindowsAzure\Services\Queue\QueueRestProxy::listMessages
    * @covers WindowsAzure\Services\Queue\QueueRestProxy::updateMessage
    */
    public function testUpdateMessageNoOptions() {
        // TODO: revert change when fixed
        // https://github.com/WindowsAzure/azure-sdk-for-php/issues/99
        // $interestingVisibilityTimes = array(-1, 0, QueueServiceFunctionalTestData::INTERESTING_TTL, QueueServiceFunctionalTestData::INTERESTING_TTL * 2);
        $interestingVisibilityTimes = array(-1, QueueServiceFunctionalTestData::INTERESTING_TTL, QueueServiceFunctionalTestData::INTERESTING_TTL * 2);

        $startingMessage = new CreateMessageOptions();
        
        // TODO: Revert when fixed
        // https://github.com/WindowsAzure/azure-sdk-for-php/issues/101
//        $startingMessage->setTimeout(QueueServiceFunctionalTestData::INTERESTING_TTL);

        
            // TODO: Revert when fixed
            // https://github.com/WindowsAzure/azure-sdk-for-php/issues/69
//        $startingMessage->setTimeToLiveInSeconds(QueueServiceFunctionalTestData::INTERESTING_TTL * 1.5);
        $startingMessage->setTimeToLiveInSeconds(QueueServiceFunctionalTestData::INTERESTING_TTL * 1.5 . '');

        foreach($interestingVisibilityTimes as $visibilityTimeoutInSeconds)  {
            $this->updateMessageWorker(QueueServiceFunctionalTestData::getSimpleMessageText(), $startingMessage, $visibilityTimeoutInSeconds, null);
        }
    }

    /**
    * @covers WindowsAzure\Services\Queue\QueueRestProxy::clearMessages
    * @covers WindowsAzure\Services\Queue\QueueRestProxy::createMessage
    * @covers WindowsAzure\Services\Queue\QueueRestProxy::listMessages
    * @covers WindowsAzure\Services\Queue\QueueRestProxy::updateMessage
    */
    public function testUpdateMessage() {
        $interestingTimes = array(null, -1, 0, QueueServiceFunctionalTestData::INTERESTING_TTL, 1000);
        
        // TODO: revert change when fixed
        // https://github.com/WindowsAzure/azure-sdk-for-php/issues/99
        // $interestingVisibilityTimes = array(-1, 0, QueueServiceFunctionalTestData::INTERESTING_TTL, QueueServiceFunctionalTestData::INTERESTING_TTL * 2);
        $interestingVisibilityTimes = array(-1, QueueServiceFunctionalTestData::INTERESTING_TTL, QueueServiceFunctionalTestData::INTERESTING_TTL * 2);

        $startingMessage = new CreateMessageOptions();        
        // TODO: Revert when fixed
        // https://github.com/WindowsAzure/azure-sdk-for-php/issues/101
//        $startingMessage->setTimeout( QueueServiceFunctionalTestData::INTERESTING_TTL);

            // TODO: Revert when fixed
            // https://github.com/WindowsAzure/azure-sdk-for-php/issues/69
//        $startingMessage->setTimeToLiveInSeconds(QueueServiceFunctionalTestData::INTERESTING_TTL * 1.5);
        $startingMessage->setTimeToLiveInSeconds(QueueServiceFunctionalTestData::INTERESTING_TTL * 1.5 . '');

        foreach($interestingTimes as $timeout)  {
            foreach($interestingVisibilityTimes as $visibilityTimeoutInSeconds)  {
                $options = new QueueServiceOptions();
        // TODO: Revert when fixed
        // https://github.com/WindowsAzure/azure-sdk-for-php/issues/101
//                $options->setTimeout($timeout);
                $this->updateMessageWorker(QueueServiceFunctionalTestData::getSimpleMessageText(), $startingMessage, $visibilityTimeoutInSeconds, $options);
            }
        }
    }

    private function updateMessageWorker($messageText, $startingMessage, $visibilityTimeoutInSeconds, $options) {
        self::println( 'Trying $options: ' . self::tmptostring($options) . 
                ' and $visibilityTimeoutInSeconds: ' . self::tmptostring($visibilityTimeoutInSeconds));
        $queue = QueueServiceFunctionalTestData::$TEST_QUEUE_NAMES;
        $queue = $queue[0];

        $this->wrapper->createMessage($queue, QueueServiceFunctionalTestData::getSimpleMessageText(), $startingMessage);
        $lmr = $this->wrapper->listMessages($queue);
        $m = $lmr->getQueueMessages();
        $m = $m[0];

        try {
            if ($options == null) {
                $this->wrapper->updateMessage($queue, $m->getMessageId(), $m->getPopReceipt(), $messageText, $visibilityTimeoutInSeconds);
            }
            else {
                $this->wrapper->updateMessage($queue, $m->getMessageId(), $m->getPopReceipt(), $messageText, $visibilityTimeoutInSeconds, $options);
            }

            if ($options == null) {
                $options = new CreateMessageOptions();
            }

            if ($visibilityTimeoutInSeconds < 0) {
                $this->assertTrue(false, 'Expect negative getVisibilityTimeoutInSeconds in $options to throw');
            }
            
            // Uncomment when fixed
            // https://github.com/WindowsAzure/azure-sdk-for-php/issues/59
//            else if ($options->getTimeout() != null && $options->getTimeout() < 1) {
//                $this->assertTrue(false, 'Expect negative timeouts in $options to throw');
//            }

            // Check that the message matches
            $lmr = $this->wrapper->listMessages($queue);
            if ($visibilityTimeoutInSeconds > 0) {
                $this->assertEquals(0, count($lmr->getQueueMessages()), 'getQueueMessages() count');
                sleep(QueueServiceFunctionalTestData::INTERESTING_TTL);
                // Try again, not that the 4 second visibility has passed
                $lmr = $this->wrapper->listMessages($queue);
                if ($visibilityTimeoutInSeconds > QueueServiceFunctionalTestData::INTERESTING_TTL) {
                    $this->assertEquals(0, count($lmr->getQueueMessages()), 'getQueueMessages() count');
                }
                else {
                    $this->assertEquals(1, count($lmr->getQueueMessages()), 'getQueueMessages() count');
                    $qm = $lmr->getQueueMessages();
                    $qm = $qm[0];
                    $this->assertEquals($messageText, $qm->getMessageText(), '$qm->getMessageText');
                }
            }
            else {
                $this->assertEquals(1, count($lmr->getQueueMessages()), 'getQueueMessages() count');
                $qm = $lmr->getQueueMessages();
                $qm = $qm[0];
                $this->assertEquals($messageText, $qm->getMessageText(), '$qm->getMessageText');
            }

        }
        catch (ServiceException $e) {
            if ($options == null) {
                $options = new CreateMessageOptions();
            }

            // Uncomment when fixed
            // https://github.com/WindowsAzure/azure-sdk-for-php/issues/59
//            if ($options->getTimeout() != null && $options->getTimeout() < 1) {
//                $this->assertEquals(500, $e->getCode(), 'getCode');
//            }
//            else
            if ($visibilityTimeoutInSeconds < 0) {
                // Trying to pass bad metadata
                $this->assertEquals(400, $e->getCode(), 'getCode');
            }
            else {
                $this->assertNull($e, 'Expect positive timeouts in $options to be fine');
            }
        }
        $this->wrapper->clearMessages($queue);
    }

    // ---------------------
    // --- deleteMessage ---
    // ---------------------

    /**
    * @covers WindowsAzure\Services\Queue\QueueRestProxy::clearMessages
    * @covers WindowsAzure\Services\Queue\QueueRestProxy::createMessage
    * @covers WindowsAzure\Services\Queue\QueueRestProxy::deleteMessage
    * @covers WindowsAzure\Services\Queue\QueueRestProxy::listMessages
    */
    public function testDeleteMessageNoOptions() {
        $this->deleteMessageWorker(null);
    }

    /**
    * @covers WindowsAzure\Services\Queue\QueueRestProxy::clearMessages
    * @covers WindowsAzure\Services\Queue\QueueRestProxy::createMessage
    * @covers WindowsAzure\Services\Queue\QueueRestProxy::deleteMessage
    * @covers WindowsAzure\Services\Queue\QueueRestProxy::listMessages
    */
    public function testDeleteMessage() {
        $interestingTimes = array(null, -1, 0, QueueServiceFunctionalTestData::INTERESTING_TTL, 1000);
        foreach($interestingTimes as $timeout)  {
            $options = new QueueServiceOptions();
            $options->setTimeout($timeout);
            $this->deleteMessageWorker($options);
        }
    }

    private function deleteMessageWorker($options) {
        self::println( 'Trying $options: ' . self::tmptostring($options));
        $queue = QueueServiceFunctionalTestData::$TEST_QUEUE_NAMES;
        $queue = $queue[0];

        $this->wrapper->createMessage($queue, 'test');
        $opts = new ListMessagesOptions();
        $opts->setVisibilityTimeoutInSeconds(QueueServiceFunctionalTestData::INTERESTING_TTL);
        $lmr = $this->wrapper->listMessages($queue, $opts);
        $m = $lmr->getQueueMessages();
        $m = $m[0];

        try {
            if ($options == null) {
                $this->wrapper->deleteMessage($queue, $m->getMessageId(), $m->getPopReceipt());
            }
            else {
                $this->wrapper->deleteMessage($queue, $m->getMessageId(), $m->getPopReceipt(), $options);
            }

            if ($options == null) {
                $options = new CreateMessageOptions();
            }

            // Uncomment when fixed
            // https://github.com/WindowsAzure/azure-sdk-for-php/issues/59
//            else if ($options->getTimeout() != null && $options->getTimeout() < 1) {
//                $this->assertTrue(false, 'Expect negative timeouts in $options to throw');
//            }

            // Check that the message matches
            $lmr = $this->wrapper->listMessages($queue);
            $this->assertEquals(0, count($lmr->getQueueMessages()), 'getQueueMessages() count');

            // Wait until the popped message should be visible again->
            sleep(QueueServiceFunctionalTestData::INTERESTING_TTL + 1);
            // Try again, to make sure the message really is gone->
            $lmr = $this->wrapper->listMessages($queue);
            $this->assertEquals(0, count($lmr->getQueueMessages()), 'getQueueMessages() count');
        }
        catch (ServiceException $e) {
            // Uncomment when fixed
            // https://github.com/WindowsAzure/azure-sdk-for-php/issues/59
//            if ($options->getTimeout() != null && $options->getTimeout() < 1) {
//                $this->assertEquals(500, $e->getCode(), 'getCode');
//            }
//            else 
               {
                $this->assertNull($e, 'Expect positive timeouts in $options to be fine');
            }
        }
        $this->wrapper->clearMessages($queue);
    }

    // --------------------
    // --- listMessages ---
    // --------------------

    /**
    * @covers WindowsAzure\Services\Queue\QueueRestProxy::clearMessages
    * @covers WindowsAzure\Services\Queue\QueueRestProxy::createMessage
    * @covers WindowsAzure\Services\Queue\QueueRestProxy::listMessages
    * @covers WindowsAzure\Services\Queue\QueueRestProxy::peekMessages
    */
    public function testListMessagesNoOptions() {
        $this->listMessagesWorker(new ListMessagesOptions());
    }

    /**
    * @covers WindowsAzure\Services\Queue\QueueRestProxy::clearMessages
    * @covers WindowsAzure\Services\Queue\QueueRestProxy::createMessage
    * @covers WindowsAzure\Services\Queue\QueueRestProxy::listMessages
    * @covers WindowsAzure\Services\Queue\QueueRestProxy::peekMessages
    */
    public function testListMessages() {
        $interestingTimes = array(null, -1, 0, QueueServiceFunctionalTestData::INTERESTING_TTL, 1000);
        $interestingNums = array(null, -1, 0, 2, 10, 1000);
        foreach($interestingNums as $numberOfMessages)  {
            foreach($interestingTimes as $visibilityTimeoutInSeconds)  {
                $options = new ListMessagesOptions();
                $options->setNumberOfMessages($numberOfMessages);
                $options->setVisibilityTimeoutInSeconds($visibilityTimeoutInSeconds);
                $this->listMessagesWorker($options);
            }
        }

            // Uncomment when fixed:
            // https://github.com/WindowsAzure/azure-sdk-for-php/issues/59
//        foreach($interestingTimes as $timeout)  {
            $options = new ListMessagesOptions();
            
            // Uncomment when fixed:
            // https://github.com/WindowsAzure/azure-sdk-for-php/issues/59
            //            $options->setTimeout($timeout);
            
            $options->setNumberOfMessages(2);
            $options->setVisibilityTimeoutInSeconds(2);
            $this->listMessagesWorker($options);
//        }
    }

    private function listMessagesWorker($options) {
        self::println( 'Trying $options: ' . self::tmptostring($options));
        $queue = QueueServiceFunctionalTestData::$TEST_QUEUE_NAMES;
        $queue = $queue[0];

        // Put three messages into the queue.
        $this->wrapper->createMessage($queue, QueueServiceFunctionalTestData::getSimpleMessageText());
        $this->wrapper->createMessage($queue, QueueServiceFunctionalTestData::getSimpleMessageText());
        $this->wrapper->createMessage($queue, QueueServiceFunctionalTestData::getSimpleMessageText());

        // Default is 1 message
        $effectiveNumOfMessages = ($options == null || $options->getNumberOfMessages() == null ? 1 : $options ->getNumberOfMessages());
        $effectiveNumOfMessages = ($effectiveNumOfMessages < 0 ? 0 : $effectiveNumOfMessages);

        // Default is 30 seconds
        $effectiveVisTimeout = ($options == null || $options->getVisibilityTimeoutInSeconds() == null ? 30 : $options ->getVisibilityTimeoutInSeconds());
        $effectiveVisTimeout = ($effectiveVisTimeout < 0 ? 0 : $effectiveVisTimeout);

        $expectedNumMessagesFirst = ($effectiveNumOfMessages > 3 ? 3 : $effectiveNumOfMessages);
        $expectedNumMessagesSecond = ($effectiveVisTimeout <= 2 ? 3 : 3 - $effectiveNumOfMessages);
        $expectedNumMessagesSecond = ($expectedNumMessagesSecond < 0 ? 0 : $expectedNumMessagesSecond);

        try {
            $res = ($options == null ? $this->wrapper->listMessages($queue) : $this->wrapper->listMessages($queue, $options));

            if ($options == null) {
                $options = new ListMessagesOptions();
            }

            if ($options->getVisibilityTimeoutInSeconds() != null && $options->getVisibilityTimeoutInSeconds() < 1) {
                $this->assertTrue(false, 'Expect non-positive getVisibilityTimeoutInSeconds in $options to throw');
            }
            else if ($options->getNumberOfMessages() != null && ($options->getNumberOfMessages() < 1 || $options->getNumberOfMessages() > 32)) {
                $this->assertTrue(false, 'Expect  getNumberOfMessages < 1 or 32 < numMessages in $options to throw');
            }
            // Uncomment when fixed
            // https://github.com/WindowsAzure/azure-sdk-for-php/issues/59
//            else if ($options->getTimeout() != null && $options->getTimeout() < 1) {
//                $this->assertTrue(false, 'Expect negative timeouts in $options to throw');
//            }

            $this->assertEquals($expectedNumMessagesFirst, count($res->getQueueMessages()), 'list getQueueMessages() count');
            $opts = new PeekMessagesOptions();
            $opts->setNumberOfMessages(32);
            $pres = $this->wrapper->peekMessages($queue, $opts);
            $this->assertEquals(3 - $expectedNumMessagesFirst, count($pres->getQueueMessages()), 'peek getQueueMessages() count');

            // The visibilityTimeoutInSeconds controls when the requested messages will be visible again.
            // Wait 2.5 seconds to see when the messages are visible again.
            sleep(2.5);
            $opts = new ListMessagesOptions();
            $opts->setNumberOfMessages(32);
            $res2 = $this->wrapper->listMessages($queue, $opts);
            $this->assertEquals($expectedNumMessagesSecond, count($res2->getQueueMessages()), 'list getQueueMessages() count');
            $opts = new PeekMessagesOptions();
            $opts->setNumberOfMessages(32);
            $pres2 = $this->wrapper->peekMessages($queue, $opts);
            $this->assertEquals(0, count($pres2->getQueueMessages()), 'peek getQueueMessages() count');

            // TODO: These might get screwy if the timing gets off. Might need to use times spaces farther apart.
        }
        catch (ServiceException $e) {
            if ($options == null) {
                $options = new ListMessagesOptions();
            }

            // Uncomment when fixed
            // https://github.com/WindowsAzure/azure-sdk-for-php/issues/59
//            if ($options->getTimeout() != null && $options->getTimeout() < 1) {
//                $this->assertEquals(500, $e->getCode(), 'getCode');
//            }
            //else 
                if ($options->getVisibilityTimeoutInSeconds() != null && $options->getVisibilityTimeoutInSeconds() < 1) {
                $this->assertEquals(400, $e->getCode(), 'getCode');
            }
            else if ($options->getNumberOfMessages() != null && ($options->getNumberOfMessages() < 1 || $options->getNumberOfMessages() > 32)) {
                $this->assertEquals(400, $e->getCode(), 'getCode');
            }
            else {
                $this->assertNull($e, 'Expect positive timeouts in $options to be fine');
            }
        }
        $this->wrapper->clearMessages($queue);
    }

    // --------------------
    // --- peekMessages ---
    // --------------------

    /**
    * @covers WindowsAzure\Services\Queue\QueueRestProxy::clearMessages
    * @covers WindowsAzure\Services\Queue\QueueRestProxy::createMessage
    * @covers WindowsAzure\Services\Queue\QueueRestProxy::listMessages
    * @covers WindowsAzure\Services\Queue\QueueRestProxy::peekMessages
    */
    public function testPeekMessagesNoOptions() {
        $this->peekMessagesWorker(new PeekMessagesOptions());
    }

    /**
    * @covers WindowsAzure\Services\Queue\QueueRestProxy::clearMessages
    * @covers WindowsAzure\Services\Queue\QueueRestProxy::createMessage
    * @covers WindowsAzure\Services\Queue\QueueRestProxy::listMessages
    * @covers WindowsAzure\Services\Queue\QueueRestProxy::peekMessages
    */
    public function testPeekMessages() {
        $interestingTimes = array(null, -1, 0, QueueServiceFunctionalTestData::INTERESTING_TTL, 1000);
        $interestingNums = array(null, -1, 0, 2, 10, 1000);
        foreach($interestingNums as $numberOfMessages)  {
            $options = new PeekMessagesOptions();
            $options->setNumberOfMessages($numberOfMessages);
            $this->peekMessagesWorker($options);
        }

            // Uncomment when fixed:
            // https://github.com/WindowsAzure/azure-sdk-for-php/issues/59
//        foreach($interestingTimes as $timeout)  {
            $options = new PeekMessagesOptions();
//            $options->setTimeout($timeout);
            $options->setNumberOfMessages(2);
            $this->peekMessagesWorker($options);
//        }
    }

    private function peekMessagesWorker($options) {
        self::println( 'Trying $options: ' . self::tmptostring($options));
        $queue = QueueServiceFunctionalTestData::$TEST_QUEUE_NAMES;
        $queue = $queue[0];

        // Put three messages into the queue.
        $this->wrapper->createMessage($queue, QueueServiceFunctionalTestData::getSimpleMessageText());
        $this->wrapper->createMessage($queue, QueueServiceFunctionalTestData::getSimpleMessageText());
        $this->wrapper->createMessage($queue, QueueServiceFunctionalTestData::getSimpleMessageText());

        // Default is 1 message
        $effectiveNumOfMessages = ($options == null || $options->getNumberOfMessages() == null ? 1 : $options ->getNumberOfMessages());
        $effectiveNumOfMessages = ($effectiveNumOfMessages < 0 ? 0 : $effectiveNumOfMessages);

        $expectedNumMessagesFirst = ($effectiveNumOfMessages > 3 ? 3 : $effectiveNumOfMessages);

        try {
            $res = ($options == null ? $this->wrapper->peekMessages($queue) : $this->wrapper->peekMessages($queue, $options));

            if ($options == null) {
                $options = new PeekMessagesOptions();
            }

            if ($options->getNumberOfMessages() != null && ($options->getNumberOfMessages() < 1 || $options->getNumberOfMessages() > 32)) {
                $this->assertTrue(false, 'Expect  getNumberOfMessages < 1 or 32 < numMessages in $options to throw');
            }
            // Uncomment when fixed
            // https://github.com/WindowsAzure/azure-sdk-for-php/issues/59
//            else if ($options->getTimeout() != null && $options->getTimeout() < 1) {
//                $this->assertTrue(false, 'Expect negative timeouts in $options to throw');
//            }

            $this->assertEquals($expectedNumMessagesFirst, count($res->getQueueMessages()), 'getQueueMessages() count');
            $opts = new PeekMessagesOptions();
            $opts->setNumberOfMessages(32);
            $res2 = $this->wrapper->peekMessages($queue, $opts);
            $this->assertEquals(3, count($res2->getQueueMessages()), 'getQueueMessages() count');
            $this->wrapper->listMessages($queue);
            $opts = new PeekMessagesOptions();
            $opts->setNumberOfMessages(32);
            $res3 = $this->wrapper->peekMessages($queue, $opts);
            $this->assertEquals(2, count($res3->getQueueMessages()), 'getQueueMessages() count');
        }
        catch (ServiceException $e) {
            if ($options == null) {
                $options = new PeekMessagesOptions();
            }

            // Uncomment when fixed
            // https://github.com/WindowsAzure/azure-sdk-for-php/issues/59
//            if ($options->getTimeout() != null && $options->getTimeout() < 1) {
//                $this->assertEquals(500, $e->getCode(), 'getCode');
//            }
            //else
                if ($options->getNumberOfMessages() != null && ($options->getNumberOfMessages() < 1 || $options->getNumberOfMessages() > 32)) {
                $this->assertEquals(400, $e->getCode(), 'getCode');
            }
            else {
                $this->assertNull($e, 'Expect positive timeouts in $options to be fine');
            }
            // TODO: More checks to not fall out the end if in error?
        }
        $this->wrapper->clearMessages($queue);
    }

    // ---------------------
    // --- clearMessages ---
    // ---------------------

    /**
    * @covers WindowsAzure\Services\Queue\QueueRestProxy::clearMessages
    * @covers WindowsAzure\Services\Queue\QueueRestProxy::createMessage
    * @covers WindowsAzure\Services\Queue\QueueRestProxy::listMessages
    */
    public function testClearMessagesNoOptions() {
        $this->clearMessagesWorker(null);
    }

    /**
    * @covers WindowsAzure\Services\Queue\QueueRestProxy::clearMessages
    * @covers WindowsAzure\Services\Queue\QueueRestProxy::createMessage
    * @covers WindowsAzure\Services\Queue\QueueRestProxy::listMessages
    */
    public function testClearMessages() {
        $interestingTimes = array(null, -1, 0, QueueServiceFunctionalTestData::INTERESTING_TTL, 1000);
        foreach($interestingTimes as $timeout)  {
            $options = new QueueServiceOptions();
            $options->setTimeout($timeout);
            $this->clearMessagesWorker($options);
        }
    }

    private function clearMessagesWorker($options) {
        self::println( 'Trying $options: ' . 
                self::tmptostring($options));
        $queue = QueueServiceFunctionalTestData::$TEST_QUEUE_NAMES;
        $queue = $queue[0];

        // Put three messages into the queue.
        $this->wrapper->createMessage($queue, QueueServiceFunctionalTestData::getSimpleMessageText());
        $this->wrapper->createMessage($queue, QueueServiceFunctionalTestData::getSimpleMessageText());
        $this->wrapper->createMessage($queue, QueueServiceFunctionalTestData::getSimpleMessageText());
        // Wait a bit to make sure the messages are there.
        sleep(1);
        // Make sure the messages are there, and use a short visibility timeout to make sure the are visible again later.
        $opts = new ListMessagesOptions();
        $opts->setVisibilityTimeoutInSeconds(1);
        $opts->setNumberOfMessages(32);
        $lmr = $this->wrapper->listMessages($queue, $opts);
        $this->assertEquals(3, count($lmr->getQueueMessages()), 'getQueueMessages() count');
        sleep(2);
        try {
            if ($options == null) {
                $this->wrapper->clearMessages($queue);
            }
            else {
                $this->wrapper->clearMessages($queue, $options);
            }

            if ($options == null) {
                $options = new CreateMessageOptions();
            }

            // Uncomment when fixed
            // https://github.com/WindowsAzure/azure-sdk-for-php/issues/59
//            else if ($options->getTimeout() != null && $options->getTimeout() < 1) {
//                $this->assertTrue(false, 'Expect negative timeouts in $options to throw');
//            }

            // Wait 2 seconds to make sure the messages would be visible again.
            $opts = new ListMessagesOptions();
            $opts->setVisibilityTimeoutInSeconds(1);
            $opts->setNumberOfMessages(32);
            $lmr = $this->wrapper->listMessages($queue, $opts);
            $this->assertEquals(0, count($lmr->getQueueMessages()), 'getQueueMessages() count');
        }
        catch (ServiceException $e) {
            if ($options == null) {
                $options = new CreateMessageOptions();
            }

            // Uncomment when fixed
            // https://github.com/WindowsAzure/azure-sdk-for-php/issues/59
//            if ($options->getTimeout() != null && $options->getTimeout() < 1) {
//                $this->assertEquals(500, $e->getCode(), 'getCode');
//            }
//            else {
             {
                $this->assertNull($e, 'Expect positive timeouts in $options to be fine');
            }
        }
        $this->wrapper->clearMessages($queue);
    }
}
