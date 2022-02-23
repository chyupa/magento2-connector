# Changelog

### v1.3.3
- Fix issue with configurable products not having correct amounts
- Send configurable product properties

### v1.3.2
- Do not send order webhook to microservice when order date is null

### v1.3.1
- Retrieve order products from bundle products and send them as individual products

### v1.3.0
- Allow integration with shops that have Magento_InventoryApi module disabled

  Product stock will be retrieved as 0

- Fix issue with order total

### v1.2.1
- Fix possible issue with order total and vouchers
- Fix issue with wrong shipping price_with_tax value

### v1.2.0
- Added new option to filter order / products by Store
- Remove update product information; keep only update stock
- Remove create / update categories
- Remove create / update attributes
