# 1.1.4

* The mapping data is now stored in a SQLite database. Each UR api key has its own database : changing api key will not overwrite your mapping table.
* Added timezone definition: declare to the extension the timezone you use in your UR settings to make create and delete timers effective.
* You can now define the default alert contact in settings. This contact will be used for new monitors and updated monitors.
* You can now start or pause an existing monitor.

# 1.1.3

* Added an action to update an existing mapped monitor with domain data

# 1.1.2

* Monitors fetching with Uptime Robot API now grabs *ALL* monitors: the getMonitors method gives 50 monitors max at a time...
* Added buttons to extension index in Plesk sidebar menu and in tools and settings panel

# 1.1.1

* Changed extension name, description, version and repository

# 1.1.0

* Forked Plesk extension and added manual edit capabilities in a *Synchronize tab*
* Create a new monitor
* Delete an existing monitor
* Map a (sub)domain to an existing monitor
* Unmap a (sub)domain from its monitor

# 1.0.0

* Initial release
