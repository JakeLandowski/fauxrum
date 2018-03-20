# MVC Separation

index.php contains all of the routes and decision making for all other classes.
Database, data object and data processing classes are all located under model/classes/.
View classes are located under views/classes. All other view assets are listed under views. 

# Routing and Templates

All URLs are directed through routes in the index, which utilizes the necessary classes to do all other jobs. All routes then render templates, with their required view logic and tokens to render data.

# Database Layer

model/classes/Database.php is a hefty CRUD abstract class that handles ALL 4 CRUD operations to the database for the entire application.

# History of Commits

Shahbaz and I have both commited changes..

# Object Oriented Programming

There are over 10 classes most using a primary class hierarchy of DataCore => Validator => Etc or just DataCore => Etc

# Docblocks

Most if not all classes have the required documentation.

# jQuery and Ajax

There is client side javascript validation for login and registration currently, and jQuery for ajaxing a random number fact API for aiding in generating posts.

# API

As above, using a random number API.

![alt text](https://github.com/JakeLandowski/fauxrum/blob/master/DataCoreUML.png)
