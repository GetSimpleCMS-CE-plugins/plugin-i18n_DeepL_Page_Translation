## General
This plugin allows translation of GetSimple cms pages using the DeepL translation API. \
It requires the [i18n base plugin](https://github.com/GetSimpleCMS-CE-plugins/plugin-i18n/) designed by Martin Vlcek.

This plugin creates new, translated pages on the Edit page, according to the i18n page structure that uses a slug ending consisting of an underscore plus the two-letter language code for non-default language pages. \
It does this by cloning pages to a different language slug, while translating their content using DeepL.

**Attention:** A (Free or Pro) DeepL Developer API account is also required: See [here](https://www.deepl.com/en/pro-api#api-pricing).

## Features
These are some of the configuration options:
* Choose which fields you want to have translated,
* which html/xml tags will contain text not to translate,
* and which expressions and words shall never be translated
* exclude placeholders {% xxx%}, (% xxx%), %xxx% from translation.
* Configure a list of target languages used, and their respective slug extensions (source language is always auto-detected). \
When new languages will be added by DeepL, simply open the plugin configuration page once and the additional language will become available.
* Give DeepL some context hints for improved translation results

## Supported Languages
This plugin works with all languages provided by DeepL. \
Currently (December 2024) DeepL says they support about 30 languages. See also here: [Supported Languages](https://developers.deepl.com/docs/resources/supported-languages)

## Installation
Unzip the zip file and upload the contents to the plugin directory.
Activate the plugin.

Then go to the Plugins Page and choose "Configure DeepL Translation" from the sidebar.
At first, you will have to enter the DeepL authenthication key you got with your DeepL API account and click "Save Settings".

After this, you will see all available languages, and you can configure all translation parameters according to your needs.
You should pay special attention to the slug configuration, as these shall reflect your multilanguage website slug structure. Make sure that for the default language the slug extension field is empty.

After saving again, the plugin is ready for use.

## Usage
When you are on the Edit page, you can either choose "Translate" from the options menu or "DeepL Translate Page" from the sidebar.

A dialog popup window will appear.

From the pulldown menu, choose the language you want the page to be translated to (You had previously defined the available selection in the plugin configuration) . The resulting page slug will be displayed, and you can choose whether you want to have a counter added to the slug in case the target language page already exists. Otherwise, an existing page will be overwritten.
After this, press "Translate".

A new machine-translated page will be created and saved, with all desired fields translated, and it will be presented in the Edit page.
Review and improve the translated content and all fields carefully and save again.

## Improving the translation
Because DeepL uses the text context for better translation, you can use an additional "context" field for giving DeepL more information what the text to translate is about. If used, it should contain a few sentences in source (default) language. \
So if your web site is about data "tables", you drop some text about Excel, tables, rows, columns, and spreadsheets there. 
Or you write about furniture, chairs and sideboards if DeepL shall translate "table" in that context.

## Disclaimer
This plugin is unofficial and is not provided by DeepL, and there are no relations or dependencies to DeepL.
