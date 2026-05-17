/**
 * Tests for the $.klass OOP system and supporting utilities.
 * Source: framework/Web/Javascripts/source/prado/prado.js
 *
 * $.klass, $.argumentNames, $.bind, $.wrap, $.delegate are the backbone
 * of every PRADO control. This file verifies the inheritance chain, the
 * $super call mechanism, and the utility functions that support it.
 */

import '../adapters/prado-core.js'; // side-effect: loads prado.js + jQuery extensions

// ─── $.argumentNames ─────────────────────────────────────────────────────────

describe('$.argumentNames', () => {
	it('returns an empty array for a zero-argument function', () => {
		expect($.argumentNames(function () {})).toEqual([]);
	});

	it('returns argument names for a single-argument function', () => {
		expect($.argumentNames(function (a) {})).toEqual(['a']); // eslint-disable-line no-unused-vars
	});

	it('returns all argument names for a multi-argument function', () => {
		expect($.argumentNames(function (a, b, c) {})).toEqual(['a', 'b', 'c']); // eslint-disable-line no-unused-vars
	});

	it('handles a function whose first argument is $super', () => {
		expect($.argumentNames(function ($super, x) {})[0]).toBe('$super'); // eslint-disable-line no-unused-vars
	});
});

// ─── $.bind ──────────────────────────────────────────────────────────────────

describe('$.bind', () => {
	it('calls the function with the given scope', () => {
		const scope = { value: 42 };
		const fn = $.bind(function () { return this.value; }, scope);
		expect(fn()).toBe(42);
	});

	it('forwards arguments', () => {
		const scope = {};
		const fn = $.bind(function (a, b) { return a + b; }, scope);
		expect(fn(3, 4)).toBe(7);
	});
});

// ─── $.wrap ──────────────────────────────────────────────────────────────────

describe('$.wrap', () => {
	it('passes the original function as the first argument to the wrapper', () => {
		const original = function () { return 'original'; };
		const wrapped = $.wrap(original, function (orig) {
			return 'wrapped(' + orig() + ')';
		});
		expect(wrapped()).toBe('wrapped(original)');
	});
});

// ─── $.klass — basic construction ────────────────────────────────────────────

describe('$.klass — basic class construction', () => {
	it('calls initialize() on new instance', () => {
		let called = false;
		const Klass = $.klass({ initialize: function () { called = true; } });
		new Klass();
		expect(called).toBe(true);
	});

	it('provides a default no-op initialize when none is supplied', () => {
		const Klass = $.klass({ foo: function () { return 1; } });
		expect(() => new Klass()).not.toThrow();
	});

	it('passes constructor arguments to initialize()', () => {
		const Klass = $.klass({ initialize: function (a, b) { this.sum = a + b; } });
		const inst = new Klass(3, 4);
		expect(inst.sum).toBe(7);
	});

	it('sets klass.superclass to null for a root class', () => {
		const Klass = $.klass({});
		expect(Klass.superclass).toBeNull();
	});

	it('sets klass.subclasses to an empty array for a root class', () => {
		const Klass = $.klass({});
		expect(Klass.subclasses).toEqual([]);
	});

	it('sets prototype.constructor to the class itself', () => {
		const Klass = $.klass({});
		const inst = new Klass();
		expect(inst.constructor).toBe(Klass);
	});
});

// ─── $.klass — inheritance ────────────────────────────────────────────────────

describe('$.klass — single-level inheritance', () => {
	const Animal = $.klass({
		initialize: function (name) { this.name = name; },
		speak: function () { return this.name + ' speaks'; },
	});

	const Dog = $.klass(Animal, {
		initialize: function (name) { this.name = name; this.type = 'dog'; },
		speak: function () { return this.name + ' barks'; },
	});

	it('subclass instance is instanceof parent', () => {
		expect(new Dog('Rex')).toBeInstanceOf(Animal);
	});

	it('subclass sets klass.superclass to the parent class', () => {
		expect(Dog.superclass).toBe(Animal);
	});

	it('parent registers subclass in parent.subclasses', () => {
		expect(Animal.subclasses).toContain(Dog);
	});

	it('subclass methods override parent methods', () => {
		expect(new Dog('Rex').speak()).toBe('Rex barks');
	});

	it('subclass initialise sets own properties', () => {
		const d = new Dog('Rex');
		expect(d.type).toBe('dog');
		expect(d.name).toBe('Rex');
	});

	it('parent methods still work on parent instances', () => {
		expect(new Animal('Cat').speak()).toBe('Cat speaks');
	});
});

// ─── $.klass — $super call ────────────────────────────────────────────────────

describe('$.klass — $super call chain', () => {
	const Base = $.klass({
		greet: function () { return 'Hello from Base'; },
	});

	const Child = $.klass(Base, {
		greet: function ($super) { return $super() + ' + Child'; },
	});

	const GrandChild = $.klass(Child, {
		greet: function ($super) { return $super() + ' + GrandChild'; },
	});

	it('$super invokes the immediate parent method', () => {
		expect(new Child().greet()).toBe('Hello from Base + Child');
	});

	it('$super chains correctly through two levels', () => {
		expect(new GrandChild().greet()).toBe('Hello from Base + Child + GrandChild');
	});
});

// ─── $.delegate ───────────────────────────────────────────────────────────────

describe('$.delegate', () => {
	it('calls the matching rule when target matches selector directly', () => {
		let called = false;
		const handler = $.delegate({ 'button': function () { called = true; } });

		const btn = document.createElement('button');
		document.body.appendChild(btn);
		handler.call(document.body, { target: btn });
		document.body.removeChild(btn);

		expect(called).toBe(true);
	});

	it('does not call any rule when no selector matches', () => {
		let called = false;
		const handler = $.delegate({ 'button': function () { called = true; } });

		const div = document.createElement('div');
		document.body.appendChild(div);
		handler.call(document.body, { target: div });
		document.body.removeChild(div);

		expect(called).toBe(false);
	});
});
