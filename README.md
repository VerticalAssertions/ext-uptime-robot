# Extension Uptime Robot with create/delete capabilities

Uptime and monitoring for Plesk managed domains and subdomains through [Uptime Robot](https://uptimerobot.com).

Extension shows analytic tabs built in original project [ext-uptime-robot](https://github.com/plesk/ext-uptime-robot) and adds a Synchronize tab.
This tab shows a list of domains and subdomains defined on your local Plesk server and create or delete Uptime Robot monitors from there.

The extension builds a *mapping table* keeping track of domain/UR monitor associations.
The list columns :

* Domain ID
* Domain name with status active or not
* IP for domain and whether it's hosted on local server or not
* Mapping status : is the domain associated to a monitor or not
* Monitor status with ID

Available actions :

* Create and map a new monitor
* Delete mapped monitor
* Unmap monitor
* (Re)map domain to an existing monitor. Proposal based on hostname


![](https://www.verticalassertions.fr/sites/default/files/ext-uptime-robot.jpg)

## Acknowledgments

This fork is a quick dev to add some write capabilities to original project.
First, it was designed to automatically synchronize list of Uptime Robot monitors to list of domains managed by Plesk, but it quickly became obvious that this could result in monitoring data loss, especially if your Uptime Robot account monitors web services scattered on more than one server.
A lot of imrpovements can be done :

* Get rid of jQuery
Plesk extension documentation lacks a lot of details, especially how to use javascript framework. I had to load  jquery 1.x and Bootstrap 3.x through require.js to prevent conflict wih prototype.
* Uptime Robot API  responses are a bit confusing for a time after using *createMonitor* and *deleteMonitor* : the created/deleted monitor can disappear/reappear in *getMonitors* responses for a while. I chose to set an arbitrary delay of 120sec after these actions to block actions that could allow you to make things you don't want.
Hope Uptime Robot will improve that soon.
* Prettify confirmation dialog with built-in modal box
* Integrate an option to create monitor in Plesk (sub)domain creation form.
* Manage monitor group by Plesk subscription and add a link in subscription page to Uptime Robot group page
* Bulk actions on synchronize tab
* ...

All JS and CSS are loaded within extension only (**not** in global.js nor global.css) to prevent them to interfere

## Deployment

Add additional notes about how to deploy this on a live system

## Author

* **Jeremy Navick** - *Synchronize tab work* - [Vertical Assertions](https://github.com/VerticalAssertions)

See also the list of [maintainers](MAINTAINERS) who participated in this project.

## License

This project is licensed under the Apache licence 2.0. See [LICENSE](LICENSE) file for details

