# Woo Change Product Type

This is a plugin that adds a few new endpoints to the REST API.

_Note:_ It uses the standard WooCommerce API authentication to authenticate requests.

# API Endpoints

## `/change-product-type`

```
URL: https://example.com/wp-json/wc/v3/ydna/change-product-type
Method: POST
```

__Request body type definition:__

```ts
/** The request body (defined in TypeScript). */
type RequestBody = {
  /** Array of products to change the parent (and type) of. */
  products: {
    /** Product ID of the product to change. */
    product_id: number;
    /** Product ID of the product to set as the parent. */
    parent_id: number;
  }[];
}
```

__Response body type definition:__

```ts
/** The request body (defined in TypeScript). */
type ResponseBody = {
  /** Array of product IDs of the products that were changed. */
  changed: number[];
  /** Array of product IDs of the products that were NOT changed. */
  not_changed: number[];
  /** Array of product IDs of the products that could not be updated. */
  failed: {
    /** The ID of the product. */
    product_id: number;
    /** A message describing what caused it to fail. */
    message: string;
  }[];
}
```

__Example request body:__

```json
{
  "products": [
    {
      "product_id": 123,
      "parent_id": 987
    },
    {
      "product_id": 654,
      "parent_id": 321
    },
    {
      "product_id": 4,
      "parent_id": 99
    }
  ]
}
```

__Example response body:__

```json
{
  "changed": [
    123
  ],
  "not_changed": [
    654
  ],
  "failed": [
    {
      "product_id": 4,
      "message": "some error message"
    }
  ]
}
```

Updates one or more products at once.

It requires you to provide the `product_id` of the product you want to change and also the `parent_id` of the product that is supposed to be the parent.

__Purpose:__ It is not possible to change the `type` of a product to `variation` using the WooCommerce REST API or user interface.

## `/change-product-type-to-simple`

```
URL: https://example.com/wp-json/wc/v3/ydna/change-product-type-to-simple
Method: POST
```

__Request body type definition:__

```ts
/** The request body (defined in TypeScript). */
type RequestBody = {
  /** Product IDs of products to change. Sets their 'type' to "simple" and removes their parent. */
  product_ids: number[];
}
```

__Response body type definition:__

```ts
/** The request body (defined in TypeScript). */
type ResponseBody = {
  /** Array of product IDs of the products that were changed. */
  changed: number[];
  /** Array of product IDs of the products that were NOT changed. */
  not_changed: number[];
  /** Array of product IDs of the products that could not be updated. */
  failed: {
    /** The ID of the product. */
    product_id: number;
    /** A message describing what caused it to fail. */
    message: string;
  }[];
}
```

__Example request body:__

```json
{
  "product_ids": [
    123,
    654,
    4
  ]
}
```

__Example response body:__

```json
{
  "changed": [
    123
  ],
  "not_changed": [
    654
  ],
  "failed": [
    {
      "product_id": 4,
      "message": "some error message"
    }
  ]
}
```

Updates one or more products at once.

It sets the `type` to `"simple"` and removes the parent (if there is any). It does nothing unless the product is a variation product.

__Purpose:__ It is not possible to change the `type` of a product to `simple` (if they are of type `variation`) using the WooCommerce REST API or user interface.
