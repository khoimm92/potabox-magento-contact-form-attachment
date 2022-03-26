<?php

namespace PotaBox\ContactFormAttachment\Model;

use Magento\Contact\Model\ConfigInterface;
use Magento\Framework\App\Area;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\RequestInterface;
use Magento\MediaStorage\Model\File\UploaderFactory;

class Mail extends \Magento\Contact\Model\Mail
{
    /**
     * @var ConfigInterface
     */
    private $contactsConfig;

    /**
     * @var TransportBuilder
     */
    private $transportBuilder;

    /**
     * @var StateInterface
     */
    private $inlineTranslation;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * Mail constructor.
     * @param ConfigInterface $contactsConfig
     * @param TransportBuilder $transportBuilder
     * @param StateInterface $inlineTranslation
     * @param RequestInterface $request
     * @param UploaderFactory $fileUploaderFactory
     * @param StoreManagerInterface|null $storeManager
     */
    public function __construct(
        ConfigInterface $contactsConfig,
        TransportBuilder $transportBuilder,
        StateInterface $inlineTranslation,
        RequestInterface $request,
        StoreManagerInterface $storeManager = null
    ) {
        parent::__construct($contactsConfig, $transportBuilder, $inlineTranslation, $storeManager);
        $this->contactsConfig = $contactsConfig;
        $this->transportBuilder = $transportBuilder;
        $this->inlineTranslation = $inlineTranslation;
        $this->storeManager = $storeManager ?: ObjectManager::getInstance()->get(StoreManagerInterface::class);
        $this->request = $request;
    }

    /**
     * @param string $replyTo
     * @param array $variables
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\MailException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function send($replyTo, array $variables)
    {
        /** @see \Magento\Contact\Controller\Index\Post::validatedParams() */
        $replyToName = !empty($variables['data']['name']) ? $variables['data']['name'] : null;

        $this->inlineTranslation->suspend();
        try {

            $attachments = $this->processAttachments();

            $transport = $this->transportBuilder
                ->setTemplateIdentifier($this->contactsConfig->emailTemplate())
                ->setTemplateOptions(
                    [
                        'area' => Area::AREA_FRONTEND,
                        'store' => $this->storeManager->getStore()->getId()
                    ]
                )
                ->setTemplateVars($variables)
                ->setFrom($this->contactsConfig->emailSender())
                ->addTo($this->contactsConfig->emailRecipient())
                ->setReplyTo($replyTo, $replyToName);
            foreach ($attachments as $attachment) {
                $transport->addAttachment($attachment['content'], $attachment['name'], $attachment['type']);
            }
            $transport->getTransport()->sendMessage();
        } finally {
            $this->inlineTranslation->resume();
        }
    }

    /**
     * @return array
     * @throws \Exception
     */
    protected function processAttachments(): array
    {
        $result = [];
        $filesData = $this->request->getFiles('image');
        foreach ($filesData as $file) {
            if (!empty($file['name']) && !empty($file['tmp_name'])) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $file['tmp_name']);
                if (!in_array($mimeType, ['image/jpg', 'image/jpeg', 'image/gif', 'image/png', 'image/webp'])) {
                    continue;
                }
                $result[] = [
                    'content' => file_get_contents($file['tmp_name']),
                    'name' => $file['name'],
                    'type' => mime_content_type($file['tmp_name']),
                ];
            }
        }
        return $result;
    }
}
