const { createYoga, createSchema } = require('graphql-yoga');
const { createServer } = require('node:http');
const fs = require('node:fs');
const path = require('node:path');
const sqlite3 = require('sqlite3');
const { open } = require('sqlite');

const schemaPath = path.join(__dirname, 'schema.graphql'); // Using path for safe schema file access
const typeDefs = fs.readFileSync('./src/schema.graphql', 'utf8');

let db;

async function setupDatabase() {
    db = await open({
        filename: path.join(__dirname, 'database.sqlite'),
        driver: sqlite3.Database
    });

    await db.exec(`
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            email TEXT NOT NULL,
            login TEXT NOT NULL
        );
        CREATE TABLE IF NOT EXISTS todos (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            userId INTEGER NOT NULL,
            FOREIGN KEY(userId) REFERENCES users(id) ON DELETE CASCADE
        );
    `);
}

const resolvers = {
  Query: {
    users: () => db.all('SELECT * FROM users'),
    user: (_, args) => db.get('SELECT * FROM users WHERE id = ?', [args.id]),
    todos: () => db.all('SELECT * FROM todos'),
    todo: (_, args) => db.get('SELECT * FROM todos WHERE id = ?', [args.id]),
  },
  
  Mutation: {
    //USER
    addUser: async (_, args) => {
        const result = await db.run(
            'INSERT INTO users (name, email, login) VALUES (?, ?, ?)', 
            [args.name, args.email, args.login]
        );
        return db.get('SELECT * FROM users WHERE id = ?', [result.lastID]);
    },

    updateUser: async (_, args) => {
        // COALESCE makes it so that if a value isntt provided, we use the old one
        await db.run(`
            UPDATE users 
            SET name = COALESCE(?, name), 
                email = COALESCE(?, email), 
                login = COALESCE(?, login) 
            WHERE id = ?`, 
            [args.name, args.email, args.login, args.id]
        );
        return db.get('SELECT * FROM users WHERE id = ?', [args.id]);
    },

    deleteUser: async (_, args) => {
        const result = await db.run('DELETE FROM users WHERE id = ?', [args.id]);
        return result.changes > 0; // Return true if any rows were deleted
    },

    // TODO
    addTodo: async (_, args) => {
        const result = await db.run(
            'INSERT INTO todos (title, userId) VALUES (?, ?)', 
            [args.title, args.userId]
        );
        return db.get('SELECT * FROM todos WHERE id = ?', [result.lastID]);
    },

    updateTodo: async (_, args) => {
        await db.run(`
            UPDATE todos 
            SET title = COALESCE(?, title), 
                userId = COALESCE(?, userId) 
            WHERE id = ?`, 
            [args.title, args.userId, args.id]
        );
        return db.get('SELECT * FROM todos WHERE id = ?', [args.id]);
    },

    deleteTodo: async (_, args) => {
        const result = await db.run('DELETE FROM todos WHERE id = ?', [args.id]);
        return result.changes > 0;
    }
  },

  User: {
    todos: (parent) => db.all('SELECT * FROM todos WHERE userId = ?', [parent.id])
  },
  
  ToDoItem: {
    user: (parent) => db.get('SELECT * FROM users WHERE id = ?', [parent.userId])
  },
};

const schema = createSchema({ typeDefs, resolvers });
const yoga = createYoga({ schema });
const server = createServer(yoga);

setupDatabase().then(() => {
    server.listen(4000, () => {
      console.log('Server is running on http://localhost:4000');
    });
}).catch(err => {
    console.error("DB error:", err);
});