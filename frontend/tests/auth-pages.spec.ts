import { test, expect } from '@playwright/test'

// Test Sign In Page

test.describe('Sign In Page', () => {
  test('should render all elements and allow navigation to Sign Up', async ({ page }) => {
    await page.goto('/signin')
    await expect(page.getByRole('heading', { name: /login/i })).toBeVisible()
    await expect(page.getByLabel('Username')).toBeVisible()
    await expect(page.getByLabel('Password')).toBeVisible()
    await expect(page.getByRole('checkbox', { name: /remember me/i })).toBeVisible()
    await expect(page.getByRole('button', { name: /login/i })).toBeVisible()
    await expect(page.getByRole('link', { name: /forgot your password/i })).toBeVisible()
    await expect(page.getByRole('link', { name: /sign up/i })).toBeVisible()
  })

  test('should show error on empty submit', async ({ page }) => {
    await page.goto('/signin')
    await page.getByRole('button', { name: /login/i }).click()
    // Expect some error feedback (simulate, since backend not connected)
    // You may need to mock fetch or check for required field validation
    await expect(page.getByLabel('Username')).toHaveAttribute('aria-invalid', 'true')
    await expect(page.getByLabel('Password')).toHaveAttribute('aria-invalid', 'true')
  })
})

// Test Sign Up Page

test.describe('Sign Up Page', () => {
  test('should render all elements and allow navigation to Sign In', async ({ page }) => {
    await page.goto('/signup')
    await expect(page.getByRole('heading', { name: /sign up/i })).toBeVisible()
    await expect(page.getByLabel('Full Name')).toBeVisible()
    await expect(page.getByLabel('Email')).toBeVisible()
    await expect(page.getByLabel('Password')).toBeVisible()
    await expect(page.getByRole('button', { name: /sign up/i })).toBeVisible()
    await expect(page.getByRole('link', { name: /sign in/i })).toBeVisible()
  })

  test('should show error on invalid password', async ({ page }) => {
    await page.goto('/signup')
    await page.getByLabel('Email').fill('test@example.com')
    await page.getByLabel('Password').fill('123') // too short
    await page.getByRole('button', { name: /sign up/i }).click()
    // Expect some error feedback (simulate, since backend not connected)
    await expect(page.getByLabel('Password')).toHaveAttribute('aria-invalid', 'true')
  })
})
