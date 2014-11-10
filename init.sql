CREATE TABLE session (
	id bigserial not null primary key,
	ipAddress varchar(255) not null,
	studentNumber varchar(255) not null,
	added timestamp not null default now()
);

CREATE TABLE entry (
	id bigserial not null primary key,
	sessionId bigint references session,
	time timestamp not null,
	content varchar(255)
);