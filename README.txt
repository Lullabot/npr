0) The modules will do ABSOLUTELY NOTHING without an NPR API key. Get one at http://api.npr.org
1) Download and install modules as usual.
2) npr_api does not do much by itself, unless you are writing your own (custom) module which depends on it it
3) Set your API key at /admin/config/services/npr/api_config
4) npr_api_pull module will retrieve stories from the API:
  4a) either one at a time, at: /admin/content/npr
  4b) or automatically, via cron, at: /admin/config/services/npr/api_config
