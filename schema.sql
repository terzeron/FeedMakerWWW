create table feed (
       feed_name varchar(256),
       feed_alias varchar(256),
       category varchar(256),
       requested_by_http boolean,
       requsted_time datetime,
       rendered_in_app boolean, 
       rendered_time datetime,
       registered_in_htaccess boolean,
       registered_time datetime,
       publicized_in_web boolean,
       publicized_time datetime,
       made_by_feedmaker boolean,
       made_time datetime,
       conf json,
       primary key (feed_name, category)
);      


