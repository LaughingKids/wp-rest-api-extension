#rest-api-extension
## Overall
We create this plugin for wordpress developers to create bilingual wp websites.
Before you install this plugin, we highly recommand you to install a bunch of relative 
plugins:
* list of relative plugins
 * rest-api (required)
 * sitepress-multilingual-cms (required)
 * contact-form-7 (required)
 * advanced-custom-fields-pro
 * v_composer
* At this moment, this plugin only support 'GET' requests.

## Wordpress hook functions
* For frontend development, we add serveral hook functions to wordpress. 
* hook_point:rest_url_prefix
 * The original one has been changed to 'json-api' rather than 'wp-json'. We donot remove original endpoints which means they are still avaliable to use.
* name_save_pre&wp_insert_post_data
 * In order to simplify route register operation in frontend developement, we add a meta data for all post type named as 'react-post-slug'. All published post's post_name will be setted as same as meta's value.
* hook_point:init
 * Consider with some complicated requirement, we add two functions, which are register_customize_post_type_to_rest_api and register_customize_taxonomy_to_rest_api, to init hook. The functionality for these to methods as same as their names.
    
## Endpoints
|   end-points  |   name-space  |   rest-url    |   method      |
| ------------- | ------------- | ------------- | ------------- |
| /json-api  | /restml/$langs  | /$post_type/$post_id  | GET  |
| /json-api  | /restml/$langs  | /$post_type/slug/$post_name  | GET  |
| /json-api  | /restml/$langs  | /menus/$menu_id  | GET  |
| /json-api  | /restml/$langs  | /menus/$menu_name  | GET  |
| /json-api  | /restml/$langs  | /types  | GET  |
| /json-api  | /restml/$langs  | /wpcf/$form_id  | GET  |
| /json-api  | /restml/$langs  | /wpcf/$form_id  | POST  |
| /json-api  | /restml/$langs  | /acf/$post_id  | GET  |
| /json-api  | /restml/$langs  | /acf/$post_id/$meta-key  | GET  |
*About the parameters*

*Word posts in this documents indicates all wordpress posts with different post type, such as post, page etc.*

1. $langs are all avaliable language in wpml plugin. 
 * The value of $langs is tag property for every language in wpml. Specifically, for Chinese $langs equals to 'zh-hans' but for English $langs equals to 'en'. (More details will be found in WPML documents).
2. $post_id & $menu_id & $form_id are numbers only.
3. $post_name & $menu_name are slugs for menus and posts.

## Query parameters

We add a new filter parameter named as 'rest-extended' to make more detailed customized requests for single post.
The method of request seems like filter in original rest-api. Below table indicates the query parameter and related
values.

|   param-array  |   param-item  |   accepted_value & reponse_data    |   typical-request      |
| ------------- | ------------- | ------------- | ------------- |
| rest-extended | acf_metas | '0' = do not sent response <br /> '1' = return all acf meta values. | ?rest-extended\[acf_metas\]=1 |
| rest-extended | post_attachment | '0' = do not sent response <br /> '1' = return post feature image url to frontend. | ?rest-extended\[post_attachment\]=1 |


*post response with acf meta-values and post feature image*
```json
{
	"id": 24,
	"date": "2016-09-29T06:11:23",
	"date_gmt": "2016-09-29T06:11:23",
	"modified": "2016-10-04T01:59:04",
	"modified_gmt": "2016-10-04T01:59:04",
	"slug": "no-bugs",
	"type": "post",
	"link": "http://backend.example.com/zh-hans/no-bugs/",
	"title": {
		"rendered": "Chinese version test"
	},
	"content": {
		"rendered": "<p>testing</p>\n"
	},
	"excerpt": {
		"rendered": "<p>testing</p>\n"
	},
	"author": 1,
	"featured_media": 60,
	"comment_status": "open",
	"ping_status": "open",
	"sticky": false,
	"format": "standard",
	"category": [
		2
	],
	"post_tag": [],
	"post_format": [],
	"acf_metas": {
		"testing_field": "<p>acf meta test value for testing_field</p>\n"
	},
	"featured_media_origin_url": "/files/2016/09/Horizon.jpg"
}
```

## Supported Plugin - For Contact Form 7

Our plugin supports most popular wordpress plugin contact form 7. User will get cf-7 by form-id in json format. The sample response looks like:

```json
{
    "id": "4",
    "fields": [
        {
            "type": "hidden",
            "name": "_wpcf7",
            "value": "4"
        },
        {
            "type": "hidden",
            "name": "_wpcf7_version",
            "value": "4.5"
        },
        {
            "type": "hidden",
            "name": "_wpcf7_locale",
            "value": "en_US"
        },
        {
            "type": "hidden",
            "name": "_wpcf7_unit_tag",
            "value": "wpcf7-f4-o1"
        },
        {
            "type": "hidden",
            "name": "_wpnonce",
            "value": "e825500cb1"
        },
        {
            "type": "text",
            "name": "your-name",
            "value": "",
            "size": "40",
            "aria-required": "true",
            "aria-invalid": "false"
        },
        {
            "type": "email",
            "name": "your-email",
            "value": "",
            "size": "40",
            "aria-required": "true",
            "aria-invalid": "false"
        },
        {
            "type": "text",
            "name": "your-subject",
            "value": "",
            "size": "40",
            "aria-invalid": "false"
        },
        {
            "type": "submit",
            "value": "Send"
        }
    ]
}
```

### How to generate this json data?

Relies on simple_html_dom library, our plugin return all input element in original contact form 7 html string. The properties in json response are attributes for each input element. Front-end developers are responsible to modify inputs' labels and placeholders. 

### POST Requests

For security reasons, all post request should add wpcf-nonce in $POST['body'] which is required to identify authorities. The responce of all POST request are original WPCF responce.

## Supported Plugin - Advanced Custom Fields PRO

User can sent request to 'acf' endpoint to get post meta-values and get meta-value by meta-key.
A combination between post response and its meta-values is available to user, more detail will be
found in above section 'Query parameters'. 
In this section we will introduce group and single requests on 'acf' endpoint separately.

1. default meta value queries
   1. single request: /json-api/restml/zh-hans/acf/24/testing_field
   ```json
   {
   	"key": "testing_field",
   	"value": "<p>acf meta test value for testing_field</p>\n"
   }
   ```
   2. group request: /json-api/restml/zh-hans/acf/24
   ```json
   {
   	"testing_field": "<p>acf meta test value for testing_field</p>\n"
   }
   ```
2. Query with post body values

## Modified Wp-rest plugin - Posts Controller

## Modified Wp-rest-api-for-menu plugin - Menus Controller

This endpoint is an extension for another wordpress plugin named as 'WP API Menus' (More details from [WP API Menus](https://wordpress.org/plugins/wp-api-menus/)). 

### What do we changed?

1. All url has been change to a relative path and the backend url has been hidden.
* url structure:
 * page: /[$langFlag]/[$pageSlug]
 * post: /[$langFlag]/[$postTypeSlug]/[$postSlug]
 * taxonomy: /[$langFlag]/[$taxSlug]/[$termSlug]
 * Sample response looks like
```json
{
    "ID": 3,
    "name": "main",
    "slug": "main",
    "description": "",
    "count": 8,
    "items": [
        {
            "id": 33,
            "order": 1,
            "parent": 0,
            "title": "Home",
            "url": "http://backend.example.com/",
            "attr": "",
            "target": "",
            "classes": "",
            "xfn": "",
            "description": "",
            "object_id": 33,
            "object": "custom",
            "type": "custom",
            "type_label": "Custom Link",
            "slug": null
        },
        {
            "id": 29,
            "order": 2,
            "parent": 0,
            "title": "Sample Page",
            "url": "/en/sample-page",
            "attr": "",
            "target": "",
            "classes": "",
            "xfn": "",
            "description": "",
            "object_id": 2,
            "object": "page",
            "type": "post_type",
            "type_label": "Page",
            "slug": "sample-page"
        },
        {
            "id": 30,
            "order": 3,
            "parent": 0,
            "title": "test1",
            "url": "/en/post/test1",
            "attr": "",
            "target": "",
            "classes": "",
            "xfn": "",
            "description": "",
            "object_id": 23,
            "object": "post",
            "type": "post_type",
            "type_label": "Post",
            "slug": "test1",
            "children": [
                {
                    "id": 35,
                    "order": 4,
                    "parent": 30,
                    "title": "Sample Page",
                    "url": "/en/sample-page",
                    "attr": "",
                    "target": "",
                    "classes": "",
                    "xfn": "",
                    "description": "",
                    "object_id": 2,
                    "object": "page",
                    "type": "post_type",
                    "type_label": "Page",
                    "slug": "sample-page",
                    "children": [
                        {
                            "id": 37,
                            "order": 5,
                            "parent": 35,
                            "title": "test",
                            "url": "/en/post/input-slug-2",
                            "attr": "",
                            "target": "",
                            "classes": "",
                            "xfn": "",
                            "description": "",
                            "object_id": 17,
                            "object": "post",
                            "type": "post_type",
                            "type_label": "Post",
                            "slug": "input-slug-2"
                        },
                        {
                            "id": 34,
                            "order": 6,
                            "parent": 35,
                            "title": "Uncategorized",
                            "url": "/category/uncategorized",
                            "attr": "",
                            "target": "",
                            "classes": "",
                            "xfn": "",
                            "description": "",
                            "object_id": 1,
                            "object": "category",
                            "type": "taxonomy",
                            "type_label": "Category",
                            "slug": null,
                            "children": [
                                {
                                    "id": 36,
                                    "order": 7,
                                    "parent": 34,
                                    "title": "post-test-2",
                                    "url": "/en/post/post_test_2",
                                    "attr": "",
                                    "target": "",
                                    "classes": "",
                                    "xfn": "",
                                    "description": "",
                                    "object_id": 23,
                                    "object": "post",
                                    "type": "post_type",
                                    "type_label": "Post",
                                    "slug": "post_test_2"
                                }
                            ]
                        }
                    ]
                }
            ]
        },
        {
            "id": 38,
            "order": 8,
            "parent": 0,
            "title": "post-test-3",
            "url": "/en/post/post_test_3",
            "attr": "",
            "target": "",
            "classes": "",
            "xfn": "",
            "description": "",
            "object_id": 14,
            "object": "post",
            "type": "post_type",
            "type_label": "Post",
            "slug": "post_test_2"
        }
    ]
}
```

## Copyright and third-party codes

1. We include simple_html_dom.php in our plugin, which is not our work. You can get more details from [Simplehtmldom](http://simplehtmldom.sourceforge.net/)

2. We modified WP API Menus plugin with language request.

*We are appriciate to their contributions.*

## Togos
 * Add 'POST' requests to posts endpoints
