#!/usr/bin/tarantool

box.cfg {
    listen = 3301,
    log_level = 6,
    wal_mode = 'none',
    snap_dir = '/tmp',
    slab_alloc_arena = .1,
}

box.schema.user.grant('guest', 'read,write,execute', 'universe')

local credentials = {
    user_foo = 'foo',
    user_empty = '',
    user_big = '123456789012345678901234567890123456789012345678901234567890' -- '1234567890' * 6
}

for username, password in pairs(credentials) do
    if box.schema.user.exists(username) then
        box.schema.user.drop(username)
    end

    box.schema.user.create(username, { password = password })
end

local function create_space(name)
    if box.space[name] then
        box.space[name]:drop()
    end

    return box.schema.space.create(name, {temporary = true})
end

local space = create_space('space_conn')
space:create_index('primary', {type = 'tree', parts = {1, 'num'}})

local function create_fixtures()
    local space

    space = create_space('space_str')
    space:create_index('primary', {type = 'hash', parts = {1, 'str'}})

    space = create_space('space_num')
    space:create_index('primary', {type = 'hash', parts = {1, 'num'}})

    space = create_space('space_empty')
    space:create_index('primary', {type = 'tree', parts = {1, 'num'}})

    space = create_space('space_misc')
    space:create_index('primary', {type = 'hash', parts = {1, 'num'}})
    space:create_index('secondary', {type = 'tree', parts = {2, 'str'}})
    space:insert{1, 'foobar'}
    space:insert{2, 'replace_me'}
    space:insert{3, 'delete_me_1'}
    space:insert{4, 'delete_me_2'}
    space:insert{5, 'delete_me_3'}

    space = create_space('space_data')
    space:create_index('primary', {type = 'tree', unique = true, parts = {1, 'num'}})
    space:create_index('secondary', {type = 'tree', unique = false, parts = {2, 'num', 3, 'str'}})

    for i = 1, 100 do
        space:replace{i, i * 2 % 5, 'tuple_' .. i}
    end
end

box.session.on_connect(create_fixtures)


function func_foo()
    return {foo='foo', bar=42}
end

function func_sum(x, y)
    return x + y
end

function func_arg(arg)
    return arg
end

function func_mixed()
    return true, {
        c = {
            ['106'] = {1, 1428578535},
            ['2'] = {1, 1428578535}
        },
        pc = {
            ['106'] = {1, 1428578535, 9243},
            ['2'] = {1, 1428578535, 9243}
        },
        s = {1, 1428578535},
        u = 1428578535,
        v = {}
    }, true
end
