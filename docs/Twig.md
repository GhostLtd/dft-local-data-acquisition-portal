[Home](../README.md) > Twig

# Twig

## Page title and heading

The page title (`<title>`) and page heading (`<h1>`) are controlled via a few different variables:

#### Title

Uses the first viable candidate from the following list of variables

* `page_title`  - plain text title
* `page_title_key` / `page_title_params` - page title translation key, and optional translation params

Additionally, a `page_title_suffix` can be specified which gets appended to whatever title has been produced.

#### Heading

Uses the first viable candidate from the following list of variables

* `page_heading`  - plain text heading
* `page_heading_key` / `page_heading_params` - page heading translation key, and optional translation params
* Page title - i.e. if not overridden, the page heading defaults to the same content as the page title.

A `page_heading_class` can also be specified to override the class on the `<h1>` (defaults to `govuk-heading-l`)


 