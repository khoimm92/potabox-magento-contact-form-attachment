# [Magento 2 module] Add Attachment to Contact email

- This Magento 2 extension aims to add attachments to the email sent from the default contact form
- The attachment type, in this case, is images
- Note, this module works with Magento below 2.4.x, see TODOs section

# Install

Clone the repository then copy it to `app/code/PotaBox`, then run:

`php bin/magento setup:upgrade`

# How it works

- Rewrite the contact form template to add the upload field
- Rewrite class `Magento\Framework\Mail\Template\TransportBuilder` to add attachments to the email transport

# TODOs

Replace all Zend classes to Laminas classes, as no more Zend lib since Magento 2.4.

For example: `Zend\Mime\Mime` to `Laminas\Mime\Mime`
