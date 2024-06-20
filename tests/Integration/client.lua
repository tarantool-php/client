#!/usr/bin/env tarantool

local listen = os.getenv('TNT_LISTEN_URI')

box.cfg {
    listen = (listen == '' or listen == nil) and 3301 or listen,
    log_level = 6,
}

box.schema.user.grant('guest', 'read,write,execute,create,drop,alter', 'universe', nil, {if_not_exists = true})

function try_drop_user(username)
    if box.schema.user.exists(username) then
        box.schema.user.drop(username)
    end
end

function create_user(username, password)
    try_drop_user(username)

    return box.schema.user.create(username, {password = password})
end

function try_drop_space(name)
    if box.space[name] then
        box.space[name]:drop()
    end
end

function create_space(name)
    try_drop_space(name)

    return box.schema.space.create(name, {temporary = true})
end
