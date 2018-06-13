## Magento 2 - Cron Scheduler by KiwiCommerce

### Overview
- You can Easily setup cron job
- Manage cron job from the backend
- Have a beautiful and managed timeline feature
- See the longest running cron job on Dashboard
- Receive an email if any cron job produce a fatal error
- Find the actual load on CPU/Memory by cron job execution
- Automatically kill the long running job

### **Installation**
 
 1. Composer Installation
      - Navigate to your Magento root folder<br />
            `cd path_to_the_magento_root_directory`<br />
      - Then run the following command<br />
          `composer require kiwicommerce/module-cron-scheduler`<br/>
      - Make sure that composer finished the installation without errors.

 2. Command Line Installation
      - Backup your web directory and database.
      - Download Cron Scheduler installation package from <a href="https://github.com/kiwicommerce/magento2-cron-scheduler/releases/download/v1.0.1/kiwicommerce-cron-scheduler-v101.zip">here</a>.
      - Upload contents of the Cron Scheduler Log installation package to your Magento root directory.
      - Navigate to your Magento root folder<br />
          `cd path_to_the_magento_root_directory`<br />
      - Then run the following command<br />
          `php bin/magento module:enable KiwiCommerce_CronScheduler`<br />
      - Log out from the backend and log in again.
   
- After install the extension, run the following command <br/>
          `php bin/magento setup:upgrade`<br />
          `php bin/magento setup:di:compile`<br />
          `php bin/magento setup:static-content:deploy`<br />
          `php bin/magento cache:flush`

Find More details on <a href="https://kiwicommerce.co.uk/extensions/magento2-cron-scheduler/" target="_blank">KiwiCommerce</a>

## Features

### Cron Jobs

You will have a list of all cron jobs with their groups, cron expression, job code and other useful information.

<img src="https://kiwicommerce.co.uk/wp-content/uploads/2018/05/cronjob.png"/><br/>

### Cron Job Schedule list

You will have a list of scheduled jobs with their respective status.
<img src="https://kiwicommerce.co.uk/wp-content/uploads/2018/05/schedule-list.png"/><br/>

### Add New Cron Job

You can create a new cron job by clicking on Add New Cron Job. You need to add different valid information while creating it.

<img src="https://kiwicommerce.co.uk/wp-content/uploads/2018/05/addnewcronjob.png"/> <br/>

### Timeline

You will have a beautiful timeline for each cron job.

<img src="https://kiwicommerce.co.uk/wp-content/uploads/2018/05/timeline.png" /> <br/>

### Configuration

You need to follow this path. Stores > Configuration > KIWICOMMERCE EXTENSIONS > Cron Scheduler

<img src="https://kiwicommerce.co.uk/wp-content/uploads/2018/05/Configuration_cronscheduler.png"/> <br/>

### Dashboard

You can analyze the actual time taken by each job on magento dashboard.

<img src="https://kiwicommerce.co.uk/wp-content/uploads/2018/05/cronscheduler_dashboard.png" height="250"/> <br/>

## Contribution
Well unfortunately there is no formal way to contribute, we would encourage you to feel free and contribute by:
 
  - Creating bug reports, issues or feature requests on <a target="_blank" href="https://github.com/kiwicommerce/magento2-cron-scheduler/issues">Github</a>
  - Submitting pull requests for improvements.
    
We love answering questions or doubts simply ask us in issue section. We're looking forward to hearing from you!
 
  - Follow us <a href="https://twitter.com/KiwiCommerce">@KiwiCommerce</a>
  - <a href="mailto:support@kiwicommerce.co.uk">Email Us</a>
  - Have a look at our <a href="https://kiwicommerce.co.uk/docs/cron-scheduler/">documentation</a> 


