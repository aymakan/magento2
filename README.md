# Magento 2 module for Aymakan Integration
This plugin enables Magento 2 stores to perform the following.

- Create AWB in Aymakan
- Add a shipment to Magento 2 Order with tracking number and AWB download link

## Installation
Following are the instruction to install this module.

- Download this repository. 
- Unzip the compressed file and you will find the module under Aymakan directory.
- Upload the module to your `app/code` directory
- Run `php bin/magento module:enable Aymakan_Carrier` 
- Clear your magento 2 store cache

## Configuring Module
After installation, go to Stores --> Configuration. Then go to Sales --> Shipping Methods. There you will be able
to see Aymakan Carrier. Click on Aymakan Carrier tab to open its configurations as can be seen in below screenshot.

![Configuration](/screenshots/configuration.png?raw=true "Configuration")

There are some key configurations to note down.

- `Display Cities in Arabic`: If you want to display Aymakan cities in Arabic, then select YES. Please note that this setting will only display 
cities in Arabic while creating a shipment in Magento order view. Aymakan may still display the city name in English in AWB. 
- `Testing`: If you are testing the module, select `Yes`. Once the integration is tested, and ready to move to production
disable `Testing`, and set it to No.
- `API Key`:  API Key is used for authenticating with Aymakan Api. The API key can be found in your Aymakan account.
Login to your account and go to `Integrations`. Copy the Api Key and paste in the API Key field in Magento 2 module configuration.
- `Collection Related Data`: As can be seen in above screenshot, there are several config fields which are Collection related. 
These fields are related to your address (From where Aymakan drivers will be picking up shipments). Enter your contact information here
or enter your Warehouse address and contact information in all those fields accordingly.

## Usage
Once the module is configured properly, its time to see it in action. 

- Go to orders and Open an order which is pending
or which can be shipped. You will be able to see `Create Aymakan Shipping` at top of the order view page along with other 
buttons. Check below screenshot.

![Create Shipping Button](/screenshots/create_aymakan_shipping_button.png?raw=true "Create Shipping Button")

- By clicking on this button, the following form will display in a slide in pop up from right.

![Create Shipping Form](/screenshots/shipping_form.png?raw=true "Create Shipping Form")

- Most of the form will be already filled up for you. You will need to select a `Delivery City`. Aymakan 
only support a list of cities with proper namings. So select the desired city.
- If order is COD, then select `Yes` in `Is COD?` field.
- if Order is COD, the `COD Amount` field will already have the order total. Confirm if it is correct.
- Items field should have the total number of items (products) in this shipment.
- Pieces field should have number of pieces this shipment will have. For example, for a large shipment, 
there will be several items not fitting in a single carton, so they will be packed in multiple cartons. This field
should have the number of cartons.
- Click on `Create Shipping` button at top right to create a shipping in Aymakan. 
- Once the shipment is created, you will hav a success which will have tracking number. The order status will be changed
to desired status. If you go to Shipments tab, you will find a shipment created. View that shipment and go to 
Shipment History section at bottom of the page. There will be a message in comments section with Tracking Number and AWB 
label download link. 
- The same comment message can be found in Comments History tab.



 


 